<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'organizer_id',
        'category_id',
        'title',
        'slug',
        'description',
        'activity_type',
        'poster_url',
        'quota_total',
        'quota_remaining',
        'start_time',
        'end_time',
        'location',
        'status',
        'rejection_reason',
        'auto_post_ig',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'auto_post_ig' => 'boolean',
            'quota_total' => 'integer',
            'quota_remaining' => 'integer',
            'status' => EventStatusEnum::class,
            'activity_type' => ActivityTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<User, Event>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * @return BelongsTo<EventCategory, Event>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * @return HasMany<EventDivision, Event>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(EventDivision::class, 'event_id');
    }

    /**
     * @return HasMany<Registration, Event>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'event_id');
    }

    /**
     * @return HasMany<NotificationLog, Event>
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'event_id');
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatusEnum::PUBLISHED;
    }

    public function hasAvailableQuota(): bool
    {
        return $this->quota_remaining > 0;
    }
}
