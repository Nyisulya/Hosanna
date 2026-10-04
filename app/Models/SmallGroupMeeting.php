<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupMeeting extends Model
{
    protected $fillable = [
        'small_group_id',
        'meeting_date',
        'location',
        'topic',
        'notes',
        'attendees_count',
        'reminder_sent_at',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function smallGroup(): BelongsTo
    {
        return $this->belongsTo(SmallGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(SmallGroupMeetingAttendance::class, 'small_group_meeting_id');
    }
}
