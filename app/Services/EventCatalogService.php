<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatusEnum;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventCatalogService
{
    /**
     * Get all event categories.
     *
     * @return Collection<int, EventCategory>
     */
    public function getAllCategories(): Collection
    {
        return EventCategory::orderBy('name', 'asc')->get();
    }

    /**
     * Get paginated public published events with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function getPublicEvents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        $query = Event::query()
            ->where('status', EventStatusEnum::PUBLISHED)
            ->with(['category', 'organizer']);

        // 1. Search keyword (Title / Description) - portable case-insensitive
        if (! empty($filters['search'])) {
            $search = strtolower(trim((string) $filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        // 2. Filter by Category (ID or Slug)
        if (! empty($filters['category'])) {
            $categoryParam = (string) $filters['category'];
            if (is_numeric($categoryParam)) {
                $query->where('category_id', (int) $categoryParam);
            } else {
                $query->whereHas('category', function ($q) use ($categoryParam) {
                    $q->where('slug', $categoryParam);
                });
            }
        }

        // 3. Filter by Activity Type (PARTICIPANT_EVENT / COMMITTEE_RECRUITMENT)
        if (! empty($filters['type'])) {
            $typeParam = strtoupper(trim((string) $filters['type']));
            $query->where('activity_type', $typeParam);
        }

        return $query->orderBy('start_time', 'asc')->paginate($perPage);
    }

    /**
     * Get published event by its slug.
     *
     * @throws NotFoundHttpException
     */
    public function getPublicEventBySlug(string $slug): Event
    {
        $event = Event::where('slug', $slug)
            ->where('status', EventStatusEnum::PUBLISHED)
            ->with(['category', 'organizer', 'divisions'])
            ->first();

        if (! $event) {
            throw new NotFoundHttpException('Kegiatan tidak ditemukan atau belum dipublikasikan.');
        }

        return $event;
    }
}
