<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

#[\Illuminate\Console\Attributes\Description('[DEV] Anonymize data')]
#[\Illuminate\Console\Attributes\Signature('bar:anon')]
class BarAnon extends Command
{
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (App::environment('production')) {
            return Command::FAILURE;
        }

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
