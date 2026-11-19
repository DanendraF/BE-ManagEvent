<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegistrationStatusEnum;
use App\Enums\SelectionStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'event_id',
        'registration_type',
        'custom_answers',
        'division_pref_1',
        'division_pref_2',
        'cv_file_url',
        'selection_status',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_answers' => 'array',
            'registered_at' => 'datetime',
            'status' => RegistrationStatusEnum::class,
            'selection_status' => SelectionStatusEnum::class,
        ];
    }

    /**
     * @return BelongsTo<User, Registration>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Event, Registration>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * @return HasOne<Ticket, Registration>
     */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'registration_id');
    }

    /**
     * @return HasOne<Certificate, Registration>
     */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'registration_id');
    }
}
