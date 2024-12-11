<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BarAnon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:anon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[DEV] Anonymize data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $user->email = 'email' . $user->id . '@example.com';
            $user->name = 'User ' . $user->id;
            $user->password = Hash::make('Test12345');
            $user->save();
        }

        $this->output->success('Done!');

        return Command::SUCCESS;
    }
}
