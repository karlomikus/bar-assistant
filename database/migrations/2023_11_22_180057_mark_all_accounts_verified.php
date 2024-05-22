<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Version v3.3.0 included mail confirmation, we need to
         * set all current users to be verified for backwards compatibility
         */
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNot('password', 'deleted')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
