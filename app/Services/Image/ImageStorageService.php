<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Image;

use RuntimeException;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\Exceptions\ImageFileNotFoundException;

final class ImageStorageService
{
    /**
     * @return resource
     */
    public function readStream(Image $image)
    {
        $stream = $this->disk($image->disk)->readStream($image->file_path);
        if (!is_resource($stream)) {
            throw new ImageFileNotFoundException('Image not found at path: ' . $image->file_path);
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    public function writeOwnedStream(string $path, $stream): void
    {
        if (!$this->disk('uploads')->writeStream($path, $stream)) {
            throw new RuntimeException('Unable to write owned image upload');
        }
    }

    public function delete(Image $image): void
    {
        if ($image->storage_origin !== 'owned') {
            return;
        }

        $disk = $this->disk($image->disk);
        if ($disk->exists($image->file_path)) {
            $disk->delete($image->file_path);
        }
    }

    /**
     * Removes all owned media rows and underlying files belonging to a bar.
     *
     * The deletion order is deliberate: every image is removed through the
     * model lifecycle so the ownership check in delete() is applied exactly
     * once, and the whole loop is wrapped in a transaction. If removing one
     * image fails, the database rows are rolled back while the already
     * deleted files are skipped on a retry, so a partial failure never
     * strands files with no row from which to recover them.
     */
    public function deleteBarOwnedImages(Bar $bar): void
    {
        $barImages = (new Image())->getAllBarImages($bar->id)->merge($bar->images);

        DB::transaction(function () use ($barImages): void {
            foreach ($barImages as $image) {
                $image->delete();
            }
        });

        $this->disk('uploads')->deleteDirectory('cocktails/' . $bar->id . '/');
        $this->disk('uploads')->deleteDirectory('ingredients/' . $bar->id . '/');
    }

    /**
     * @template T
     * @param callable(string): T $callback
     * @return T
     */
    public function withTemporaryFile(Image $image, callable $callback): mixed
    {
        $temporaryPath = $this->materialize($image);

        try {
            return $callback($this->disk('temp')->path($temporaryPath));
        } finally {
            $this->deleteTemporaryFile($temporaryPath);
        }
    }

    public function materialize(Image $image): string
    {
        // Copies the image onto the temp disk so downstream tooling (e.g. vips
        // image processing) can operate on a real local path instead of a stream.
        $temporaryPath = 'image-materialization/' . Str::uuid() . '.' . $image->file_extension;
        $temporaryDisk = $this->disk('temp');
        $stream = $this->readStream($image);

        try {
            if (!$temporaryDisk->writeStream($temporaryPath, $stream)) {
                throw new RuntimeException('Unable to materialize image');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $temporaryPath;
    }

    public function temporaryPath(string $path): string
    {
        return $this->disk('temp')->path($path);
    }

    public function deleteTemporaryFile(string $path): void
    {
        $this->disk('temp')->delete($path);
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function disk(string $disk)
    {
        if (!in_array($disk, ['uploads', 'catalog', 'temp'], true)) {
            throw new RuntimeException('Unsupported image storage disk: ' . $disk);
        }

        return Storage::disk($disk);
    }
}
