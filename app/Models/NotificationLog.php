<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'registration_id',
        'channel',
        'recipient',
        'delivery_status',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannelEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Event, NotificationLog>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * @return BelongsTo<Registration, NotificationLog>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
