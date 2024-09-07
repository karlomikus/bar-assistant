<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Exception;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

class ZipUtils
{
    private Filesystem $unzipDisk;
    private string $dirName;

    public function __construct()
    {
        $this->unzipDisk = Storage::disk('temp');
        $this->dirName = Str::random(8) . '/';
    }

    public function unzip(string $filename): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new Exception(sprintf('Unable to open zip file: "%s"', $filename));
        }
        $zip->extractTo($this->unzipDisk->path($this->dirName));
        $zip->close();
    }

    public function getDirName(): string
    {
        return $this->dirName;
    }

    public function cleanup(): void
    {
        $this->unzipDisk->deleteDirectory($this->dirName);
    }
}
