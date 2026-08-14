<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Promote all existing moderators to admins
        DB::table('bar_memberships')
            ->where('user_role_id', 2)
            ->update(['user_role_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse: we don't know which admins were originally moderators
    }
};
