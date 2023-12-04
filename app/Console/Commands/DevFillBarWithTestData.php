<?php

namespace Kami\Cocktail\Console\Commands;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\UserRoleEnum;

class DevFillBarWithTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:test-data {barId} {usersCount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $barId = $this->argument('barId');
        $userCount = (int) $this->argument('usersCount');
        $bar = Bar::findOrFail($barId);
        $cocktails = Cocktail::where('bar_id', $barId)->get();

        $progress = $this->output->createProgressBar($userCount);

        DB::transaction(function () use ($bar, $cocktails, $userCount, $progress) {
            $users = User::factory()->count($userCount)->create();
            foreach ($users as $user) {
                $user->joinBarAs($bar, UserRoleEnum::General);

                foreach ($cocktails as $cocktail) {
                    $rating = random_int(1, 5);
                    $cocktail->rate($rating, $user->id);
                }
                $progress->advance();
            }
        });

        $progress->finish();

        return Command::SUCCESS;
    }
}
