<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Throwable;
use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BarDeleteUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:delete-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user and all his data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userEmail = $this->argument('email');

        try {
            $user = User::where('email', $userEmail)->firstOrFail();
        } catch (Throwable $e) {
            $this->output->error('User not found!');

            return Command::FAILURE;
        }

        $this->comment('Found user:');
        $this->comment('ID: ' . $user->id);
        $this->comment('Name: ' . $user->name);
        $this->comment('Email: ' . $user->email);
        $this->comment('Total bars user belongs to: ' . $user->memberships->count());
        $this->comment('Total bars user created: ' . $user->ownedBars->count());
        $this->newLine();
        $this->alert('Please note, this will delete all cocktails, ingredients and bars that this user created!');

        $delete = $this->confirm('Are you sure you want to delete this user?');

        if ($delete) {
            DB::beginTransaction();
            try {
                DB::table('bars')->where('created_user_id', $user->id)->delete();
                DB::table('cocktails')->where('created_user_id', $user->id)->delete();
                DB::table('ingredients')->where('created_user_id', $user->id)->delete();
                DB::table('images')->where('created_user_id', $user->id)->delete();

                $user->delete();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
            DB::commit();

            $this->output->success('User deleted successfully!');

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }
}
