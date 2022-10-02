<?php
declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Tag;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Cocktail;
use Intervention\Image\ImageManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemManager;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Exceptions\CocktailException;

class CocktailService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly ImageManager $image,
        private readonly FilesystemManager $filesystem,
    ) {
    }

    /**
     * Create a new cocktail
     *
     * @param string $name
     * @param string $instructions
     * @param array $ingredients
     * @param string|null $description
     * @param string|null $cocktailSource
     * @param string|null $imageAsBase64
     * @param array<string> $tags
     * @return \Kami\Cocktail\Models\Cocktail
     */
    public function createCocktail(
        string $name,
        string $instructions,
        array $ingredients,
        ?string $description = null,
        ?string $cocktailSource = null,
        ?string $imageAsBase64 = null,
        array $tags = [],
    ): Cocktail
    {
        $this->db->beginTransaction();

        try {
            $cocktail = new Cocktail();
            $cocktail->name = $name;
            $cocktail->instructions = $instructions;
            $cocktail->description = $description;
            $cocktail->source = $cocktailSource;
            $cocktail->save();

            foreach($ingredients as $ingredient) {
                $cIngredient = new CocktailIngredient();
                $cIngredient->ingredient_id = $ingredient['ingredient_id'];
                $cIngredient->amount = $ingredient['amount'];
                $cIngredient->units = $ingredient['units'];
                $cIngredient->sort = $ingredient['sort'];

                $cocktail->ingredients()->save($cIngredient);
            }

            $dbTags = [];
            foreach($tags as $tag) {
                $tag = new Tag();
                $tag->name = $tag;
                $tag->save();
                $dbTags[] = $tag->id;
            }

            $cocktail->tags()->attach($dbTags);

        } catch (Throwable $e) {
            $this->log->error('[COCKTAIL_SERVICE] ' . $e->getMessage());
            $this->db->rollBack();

            throw new CocktailException('Error occured while creating a cocktail!', 0, $e);
        }

        $this->db->commit();

        if ($imageAsBase64) {
            try {
                $image = $this->image->make($imageAsBase64);
                $imageName = sprintf('%s_%s.jpg', $cocktail->id, Str::slug($name));

                if ($this->filesystem->disk('public')->put('cocktails/' . $imageName, $image->encode('jpg'))) {
                    $cocktail->image_path = $imageName;
                    $cocktail->save();
                }
            } catch (Throwable $e) {
                $this->log->error('[COCKTAIL_SERVICE] File upload error. ' . $e->getMessage());
            }
        }

        $this->log->info('[COCKTAIL_SERVICE] Cocktail "' . $name . '" created with id: ' . $cocktail->id);

        return $cocktail;
    }
}
