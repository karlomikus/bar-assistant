<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use RuntimeException;
use Illuminate\Contracts\Filesystem\Filesystem;

final class StarterMediaCatalogService
{
    public function releaseVersion(): string
    {
        $manifest = json_decode((string) file_get_contents(resource_path('data/starter-media-release.json')), true);
        if (!is_array($manifest)) {
            throw new RuntimeException('Starter media release manifest is invalid');
        }
        $version = $manifest['version'] ?? null;

        if (!is_string($version) || preg_match('/^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/', $version) !== 1) {
            throw new RuntimeException('Starter media release manifest must contain a YYYY.MM.DD version');
        }

        return $version;
    }

    /**
     * @return array<string, array{key: string, checksum: string}>
     */
    public function sourceObjects(Filesystem $source): array
    {
        $objects = [];
        foreach (['cocktails', 'ingredients', 'glasses'] as $resource) {
            foreach ($source->directories($resource) as $directory) {
                $dataPath = $directory . '/data.json';
                if (!$source->exists($dataPath)) {
                    continue;
                }

                $data = json_decode((string) $source->get($dataPath), true);
                if (!is_array($data)) {
                    throw new RuntimeException('Invalid starter data file: ' . $dataPath);
                }

                $images = $data['images'] ?? [];
                if (!is_array($images)) {
                    throw new RuntimeException('Starter image data is invalid in: ' . $dataPath);
                }

                foreach ($images as $image) {
                    $uri = is_array($image) ? $image['uri'] ?? null : null;
                    if (!is_string($uri)) {
                        throw new RuntimeException('Starter image URI is missing in: ' . $dataPath);
                    }

                    $relativePath = ltrim((string) (parse_url($uri, PHP_URL_PATH) ?: $uri), '/');
                    if ($relativePath === '' || str_contains($relativePath, '..')) {
                        throw new RuntimeException('Invalid starter image URI: ' . $uri);
                    }

                    $sourcePath = $directory . '/' . $relativePath;
                    if (!$source->exists($sourcePath)) {
                        throw new RuntimeException('Starter image does not exist: ' . $sourcePath);
                    }

                    $objects[$sourcePath] = [
                        'key' => $sourcePath,
                        'checksum' => $this->checksum($source, $sourcePath),
                    ];
                }
            }
        }

        ksort($objects);

        return $objects;
    }

    /**
     * @param array<string, array{key: string, checksum: string}> $objects
     */
    public function assertCompletedRelease(Filesystem $catalog, string $version, array $objects): void
    {
        $manifestPath = $this->completionManifestPath($version);
        if (!$catalog->exists($manifestPath)) {
            throw new RuntimeException('Starter catalog release is not published: ' . $version);
        }

        $manifest = json_decode((string) $catalog->get($manifestPath), true);
        if (!is_array($manifest) || ($manifest['version'] ?? null) !== $version || ($manifest['objects'] ?? null) !== $objects) {
            throw new RuntimeException('Starter catalog release manifest is invalid: ' . $version);
        }

        $this->assertObjectsMatch($catalog, $version, $objects);
    }

    /**
     * @param array<string, array{key: string, checksum: string}> $objects
     */
    public function assertObjectsMatch(Filesystem $catalog, string $version, array $objects): void
    {
        foreach ($objects as $sourcePath => $object) {
            $catalogPath = 'catalog/' . $version . '/' . $object['key'];
            if (!$catalog->exists($catalogPath) || $this->checksum($catalog, $catalogPath) !== $object['checksum']) {
                throw new RuntimeException('Starter catalog object is incomplete or changed: ' . $sourcePath);
            }
        }
    }

    public function completionManifestPath(string $version): string
    {
        return 'catalog/' . $version . '/completion-manifest.json';
    }

    private function checksum(Filesystem $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (!is_resource($stream)) {
            throw new RuntimeException('Unable to read starter media: ' . $path);
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
