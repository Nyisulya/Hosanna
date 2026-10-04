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
        Schema::table('small_group_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('small_group_meetings', 'location')) {
                $table->string('location')->nullable()->after('meeting_date');
            }
            if (!Schema::hasColumn('small_group_meetings', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('attendees_count');
            }
        });

        if (!Schema::hasTable('small_group_meeting_attendances')) {
            Schema::create('small_group_meeting_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('small_group_meeting_id')->constrained('small_group_meetings')->onDelete('cascade');
                $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
                $table->enum('status', ['present', 'absent'])->default('present');
                $table->string('sms_status')->nullable(); // 'sent', 'failed', etc.
                $table->timestamp('sms_sent_at')->nullable();
                $table->timestamps();

                $table->unique(['small_group_meeting_id', 'member_id'], 'sg_meeting_member_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('small_group_meeting_attendances');

        Schema::table('small_group_meetings', function (Blueprint $table) {
            if (Schema::hasColumn('small_group_meetings', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('small_group_meetings', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });
    }
};
