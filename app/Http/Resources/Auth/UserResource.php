<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nim' => $this->nim,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'faculty' => $this->faculty,
            'study_program' => $this->study_program,
            'role' => $this->roles->first()?->name ?? 'STUDENT',
            'role_label' => $this->roles->first()?->label ?? 'Mahasiswa',
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
