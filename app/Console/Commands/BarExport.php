<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kami\Cocktail\Export\BarsToArray;
use Kami\Cocktail\Exceptions\ExportException;

class BarExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:export-zip {barId*} {--with-passwords : Export user passwords} {--with-emails : Export user emails} {--no-users : Do not include any user data} {--R|recipes : Export only recipes and recipe related data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports bar data and compresses them to a zip file. Can process multiple bar ids';

    public function __construct(private BarsToArray $exporter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barIds = $this->argument('barId');

        $this->output->info('Starting export of bars: ' . implode(', ', $barIds));

        try {
            [$meta, $export] = $this->exporter->process($barIds, !$this->option('with-passwords'), !$this->option('with-emails'), $this->option('recipes'));
        } catch (Throwable $e) {
            $this->output->error('Unable to create a data export file.');
            Log::error($e->getMessage());

            return Command::FAILURE;
        }

        $filename = storage_path(sprintf('bar-assistant/backups/%s_%s_%s.zip', Carbon::now()->format('Ymdhi'), 'bass', implode('-', $barIds)));

        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            $message = sprintf('Error creating zip archive with filepath "%s"', $filename);

            throw new ExportException($message);
        }

        $zip->addGlob(storage_path('bar-assistant/uploads/*/{' . implode(',', $barIds) . '}/*'), GLOB_BRACE, ['remove_path' => storage_path('bar-assistant')]);

        if ($metaContent = json_encode($meta)) {
            $zip->addFromString('_meta.json', $metaContent);
        }

        if ($tables = json_encode($export)) {
            $zip->addFromString('tables.json', $tables);
        }

        $zip->close();

        $this->output->success('Data exported to file: ' . $filename);

        return Command::SUCCESS;
    }
}
