<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\RoleSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new RoleSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
