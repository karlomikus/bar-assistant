<?php

declare(strict_types=1);

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BarMigrateShelf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bar:migrate-shelf {barId : Bar ID to specify bar shelf} {userId : User ID of a member in the bar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate user shelf to bar shelf. User must be a member of the bar';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $barId = (int) $this->argument('barId');
        $userId = (int) $this->argument('userId');

        $bar = Bar::findOrFail($barId);
        $user = User::findOrFail($userId);

        $membership = $user->getBarMembership($bar->id);
        if ($membership === null) {
            $this->error('User is not a member of the specified bar');

            return Command::FAILURE;
        }

        $this->output->block('Migrating user shelf to bar shelf...');

        $this->output->writeln('Current bar shelf ingredient count: ' . DB::table('bar_ingredients')->where('bar_id', $barId)->count());
        $this->output->writeln('Current user shelf ingredient count: ' . DB::table('user_ingredients')->where('bar_membership_id', $membership->id)->count());

        if (!$this->confirm('This will DELETE ALL bar shelf ingredients from selected bar (' . $bar->name . ') and replace them with user shelf ingredients (' . $user->name . '). Continue?')) {
            return Command::FAILURE;
        }

        DB::transaction(function () use ($barId, $userId) {
            DB::table('bar_ingredients')->where('bar_id', $barId)->delete();
            DB::statement(<<<SQL
                INSERT INTO bar_ingredients (bar_id, ingredient_id) 
                    SELECT bar_id, ingredient_id FROM user_ingredients 
                        JOIN bar_memberships ON user_ingredients.bar_membership_id = bar_memberships.id 
                    WHERE user_id = :user_id
                    AND bar_id = :bar_id
            SQL, ['bar_id' => $barId, 'user_id' => $userId]);
        });

        $this->output->success('Done!');

        return Command::SUCCESS;
    }
}
