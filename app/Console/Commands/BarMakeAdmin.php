<?php

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;

class BarMakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:make-admin {email : Email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert user to admin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        $user->is_admin = true;

        $user->save();

        $this->info('User updated!');

        return Command::SUCCESS;
    }
}
