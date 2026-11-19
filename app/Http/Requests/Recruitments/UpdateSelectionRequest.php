<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitments;

use App\Enums\SelectionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selection_status' => ['required', new Enum(SelectionStatusEnum::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom message validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'selection_status.required' => 'Status seleksi pendaftar wajib dipilih.',
            'notes.max' => 'Catatan seleksi maksimal 1000 karakter.',
        ];
    }
}
