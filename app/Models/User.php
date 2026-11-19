<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'nim',
        'name',
        'email',
        'password',
        'phone_number',
        'faculty',
        'study_program',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, User>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(RoleEnum|string $role): bool
    {
        $roleName = $role instanceof RoleEnum ? $role->value : $role;
        return $this->roles->contains('name', $roleName);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(RoleEnum::STUDENT);
    }

    public function isOrganizer(): bool
    {
        return $this->hasRole(RoleEnum::ORGANIZER);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPERADMIN);
    }

    /**
     * @return HasMany<Event, User>
     */
    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    /**
     * @return HasMany<Registration, User>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'user_id');
    }

    /**
     * @return HasMany<Ticket, User>
     */
    public function checkedInTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'checked_in_by');
    }
}
