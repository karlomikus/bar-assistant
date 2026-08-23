<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Services\Image\StarterMediaCatalogService;

final class PublishStarterMediaCatalog extends Command
{
    protected $signature = 'starter-media:publish';

    protected $description = 'Publish the immutable starter media catalog release';

    public function handle(StarterMediaCatalogService $catalogService): int
    {
        $source = Storage::disk('data-files');
        $catalog = Storage::disk('catalog');

        try {
            $version = $catalogService->releaseVersion($source);
            $objects = $catalogService->sourceObjects($source);
            $completionManifestPath = $catalogService->completionManifestPath($version);

            if ($catalog->exists($completionManifestPath)) {
                $catalogService->assertCompletedRelease($catalog, $version, $objects);
                $this->info('Starter media release is already published: ' . $version);

                return self::SUCCESS;
            }

            foreach ($objects as $sourcePath => $object) {
                $catalogPath = 'catalog/' . $version . '/' . $object['key'];
                if ($catalog->exists($catalogPath)) {
                    throw new \RuntimeException('Catalog object exists before release completion: ' . $catalogPath);
                }

                $stream = $source->readStream($sourcePath);

                try {
                    if (!is_resource($stream) || !$catalog->writeStream($catalogPath, $stream)) {
                        if (is_resource($stream)) {
                            $catalog->delete($catalogPath);
                        }
                        throw new \RuntimeException('Unable to publish starter image: ' . $sourcePath);
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }

            $catalogService->assertObjectsMatch($catalog, $version, $objects);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $catalog->put(
            $completionManifestPath,
            json_encode(['version' => $version, 'objects' => $objects], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        $this->info('Published starter media release: ' . $version);

        return self::SUCCESS;
    }
}
