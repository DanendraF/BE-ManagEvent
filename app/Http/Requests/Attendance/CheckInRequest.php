<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            'qr_payload' => ['required_without:ticket_code', 'nullable', 'string'],
            'ticket_code' => ['required_without:qr_payload', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qr_payload.required_without' => 'Payload hasil pemindaian QR Code atau kode tiket wajib dikirimkan.',
            'ticket_code.required_without' => 'Kode tiket atau payload QR Code wajib dikirimkan.',
        ];
    }
}
