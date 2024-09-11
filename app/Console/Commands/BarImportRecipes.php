<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use ZipArchive;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Kami\Cocktail\Models\UserRoleEnum;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\Import\FromDataPack;

class BarImportRecipes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:import-recipes {filename? : Filename relative to the bar-assistant storage volume}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import recipes exported via bar:export-recipes command';

    public function __construct(private readonly FromDataPack $importer)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var \Illuminate\Support\Facades\Storage|\Illuminate\Filesystem\FilesystemAdapter */
        $tempUnzipDisk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('bar-assistant/temp/' . Str::random(8)),
        ]);

        /** @var \Illuminate\Support\Facades\Storage|\Illuminate\Filesystem\FilesystemAdapter */
        $barAssistantDisk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('bar-assistant'),
        ]);

        $filename = $barAssistantDisk->path($this->argument('filename'));

        $zip = new ZipArchive();
        if ($zip->open($filename) !== true) {
            $this->error(sprintf('Unable to open zip file: "%s"', $filename));

            return Command::FAILURE;
        }
        $zip->extractTo($tempUnzipDisk->path('/'));
        $zip->close();

        $barId = $this->ask('Enter the id of the bar you want to import to, or leave empty to create a new one');
        if ($barId !== null) {
            $bar = Bar::findOrFail((int) $barId);
            $user = $bar->createdUser;

            $this->comment(sprintf('Using existing bar: %s - %s', $bar->id, $bar->name));
        } else {
            $barName = $this->ask('Enter new bar name');
            $userId = (int) $this->ask('Enter the id of the user you want to assign this bar to');
            $user = User::findOrFail($userId);

            $this->comment(sprintf('User with id found: %s - %s', $user->id, $user->email));

            $bar = new Bar();
            $bar->name = $barName;
            $bar->created_user_id = $userId;
            $bar->save();

            $user->joinBarAs($bar, UserRoleEnum::Admin);

            $this->comment('Bar created successfully');
        }

        if (!$this->confirm('Continue with importing the data?')) {
            $tempUnzipDisk->deleteDirectory('/');

            return Command::FAILURE;
        }

        $this->comment('Starting recipes import...');
        Cache::flush();
        $this->importer->process($tempUnzipDisk, $bar, $user, ['cocktails', 'ingredients']);

        $tempUnzipDisk->deleteDirectory('/');

        $this->output->success('Importing done!');

        return Command::SUCCESS;
    }
}
