<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('small_group_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable()->change();
            $table->unsignedBigInteger('small_group_id')->nullable()->change();
            
            try {
                // In MySQL, drop foreign key first before dropping composite unique index
                if (Schema::getConnection()->getDriverName() === 'mysql') {
                    $table->dropForeign(['member_id']);
                    $table->dropUnique('unique_member_week_question');
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                } else {
                    $table->dropUnique('unique_member_week_question');
                }
            } catch (\Exception $e) {
                // Ignore if constraint doesn't exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('small_group_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable(false)->change();
            $table->unsignedBigInteger('small_group_id')->nullable(false)->change();
            
            try {
                $table->unique(['member_id', 'week_starting', 'question_id'], 'unique_member_week_question');
            } catch (\Exception $e) {
                // Ignore
            }
        });
    }
};
