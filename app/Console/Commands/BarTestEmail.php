<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;

class BarTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:test-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to given email address';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $toEmail = $this->argument('email');

        Mail::to($toEmail)->queue(new TestEmail());

        $this->info('Test email sent to: ' . $toEmail);

        return Command::SUCCESS;
    }
}
