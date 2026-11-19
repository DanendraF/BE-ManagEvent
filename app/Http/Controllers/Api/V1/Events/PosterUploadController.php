<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PosterUploadController extends Controller
{
    use ApiResponse;

    /**
     * Upload an event poster image directly from computer.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'poster' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max
        ], [
            'poster.required' => 'Berkas gambar poster wajib dipilih.',
            'poster.image' => 'Berkas harus berupa gambar valid.',
            'poster.mimes' => 'Format gambar yang didukung adalah JPEG, PNG, JPG, dan WebP.',
            'poster.max' => 'Ukuran gambar maksimal adalah 5 Megabytes (MB).',
        ]);

        $file = $request->file('poster');
        $filename = 'poster_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('posters', $filename, 'public');

        $url = url('/storage/' . $path);

        return $this->successResponse(
            data: [
                'path' => $path,
                'url' => $url,
                'filename' => $filename,
                'size_bytes' => $file->getSize(),
            ],
            message: 'Poster kegiatan berhasil diunggah.',
            statusCode: Response::HTTP_CREATED
        );
    }
}
