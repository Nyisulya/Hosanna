<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmallGroupMeetingAttendance extends Model
{
    protected $fillable = [
        'small_group_meeting_id',
        'member_id',
        'status',
        'sms_status',
        'sms_sent_at',
    ];

    protected $casts = [
        'sms_sent_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(SmallGroupMeeting::class, 'small_group_meeting_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
