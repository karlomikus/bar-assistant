<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Support\Facades\App;
use Throwable;
use Laravel\Ai\Classification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Classification\Boolean;
use Laravel\Ai\Responses\Data\BooleanAnswer;

#[\Illuminate\Console\Attributes\Description('[DEV] Classify taste descriptors for base ingredient seed data via AI and write them into the ingredient data.json files (one-time prototype)')]
#[\Illuminate\Console\Attributes\Signature('bar:classify-ingredient-tastes
    {--provider=openai : AI provider to use for classification}
    {--model= : Classification model, defaults to the provider default}
    {--threshold=0.7 : Minimum probability for a descriptor to be assigned}
    {--max=7 : Maximum number of descriptors assigned per ingredient}
    {--limit= : Max number of ingredients to process}
    {--force : Re-classify ingredients that already have taste_descriptors}')]
class ClassifyIngredientTastes extends Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (App::isProduction()) {
            return $this->error('This command is intended for development use only.')->setCode(Command::FAILURE);
        }

        $dataPath = resource_path('data/data');
        $descriptors = $this->getDescriptors($dataPath.'/base_taste_descriptors.json');

        $threshold = (float) $this->option('threshold');
        $max = (int) $this->option('max');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $force = (bool) $this->option('force');

        $ingredients = $this->getIngredients($dataPath.'/ingredients');

        // Resume support: skip ingredients that were already classified in place
        $pending = $ingredients->filter(fn (array $ingredient): bool => $force || ! array_key_exists('taste_descriptors', $ingredient));

        if ($limit !== null) {
            $pending = $pending->take($limit);
        }

        $this->info(sprintf(
            'Classifying %d ingredients against %d taste descriptors (threshold: %.2f, max: %d) using [%s]...',
            $pending->count(),
            count($descriptors),
            $threshold,
            $max,
            (string) $this->option('provider'),
        ));

        $this->output->progressStart($pending->count());

        foreach ($pending as $id => $ingredient) {
            try {
                $tastes = $this->classify($ingredient, $descriptors, $threshold, $max);
                $this->writeTasteDescriptors($dataPath.'/ingredients/'.$id.'/data.json', $tastes);
            } catch (Throwable $e) {
                $this->error(sprintf('%s (%s): %s', is_string($ingredient['name'] ?? null) ? $ingredient['name'] : $id, $id, $e->getMessage()));
                continue;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->output->success('Done! Ingredient data.json files updated in place.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, string> normalized name => display name
     */
    private function getDescriptors(string $path): array
    {
        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        assert(is_array($rows));

        $descriptors = [];
        foreach ($rows as $row) {
            assert(is_array($row));
            assert(is_string($row['normalized_name']));
            assert(is_string($row['name']));
            $descriptors[$row['normalized_name']] = $row['name'];
        }

        return $descriptors;
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function getIngredients(string $dir): Collection
    {
        /** @var string[] $ingredientDirs */
        $ingredientDirs = File::directories($dir);

        return collect($ingredientDirs)
            ->map(fn (string $ingredientDir): string => $ingredientDir.'/data.json')
            ->filter(fn (string $path): bool => File::exists($path))
            ->mapWithKeys(function (string $path): array {
                /** @var array<string, mixed> $data */
                $data = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

                return [basename(dirname($path)) => $data];
            });
    }

    /**
     * @param  array<int, string>  $tastes
     */
    private function writeTasteDescriptors(string $path, array $tastes): void
    {
        /** @var array<string, mixed> $data */
        $data = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $data['taste_descriptors'] = $tastes;

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }

    /**
     * Classify a single ingredient and return the top descriptor names.
     *
     * @param  array<string, mixed>  $ingredient
     * @param  array<string, string>  $descriptors  normalized name => display name
     * @return array<int, string>
     */
    private function classify(array $ingredient, array $descriptors, float $threshold, int $max): array
    {
        $questions = [];
        foreach ($descriptors as $normalized => $name) {
            $questions[$normalized] = new Boolean(
                instructions: sprintf('The ingredient tastes %s.', strtolower($name)),
                criteria: [
                    'true' => 'The descriptor accurately describes a notable part of the ingredient\'s taste profile.',
                    'false' => 'The descriptor does not fit the ingredient.',
                ],
            );
        }

        $response = Classification::of([
            'name' => is_string($ingredient['name'] ?? null) ? $ingredient['name'] : '',
            'category' => $ingredient['category'] ?? null,
            'strength' => $ingredient['strength'] ?? null,
            'description' => str(is_string($ingredient['description'] ?? null) ? $ingredient['description'] : '')->limit(2000)->toString(),
        ])
            ->questions($questions)
            ->timeout(120)
            ->classify($this->option('provider'), $this->option('model'));

        return $response->collect()
            ->filter(fn (object $answer): bool => $answer instanceof BooleanAnswer)
            ->filter(fn (BooleanAnswer $answer): bool => $answer->isTrue($threshold))
            ->map(fn (BooleanAnswer $answer): float => round($answer->probability, 2))
            ->sortDesc()
            ->take($max)
            ->keys()
            ->values()
            ->all();
    }
}
