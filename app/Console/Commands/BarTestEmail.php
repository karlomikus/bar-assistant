<?php

namespace Kami\Cocktail\Console\Commands;

use Illuminate\Console\Command;
use Kami\Cocktail\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;

#[\Illuminate\Console\Attributes\Description('Send a test email to given email address')]
#[\Illuminate\Console\Attributes\Signature('bar:test-email {email}')]
class BarTestEmail extends Command
{
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
