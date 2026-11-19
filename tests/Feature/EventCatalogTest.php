<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\EventCategorySeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            EventCategorySeeder::class,
            UserSeeder::class,
            EventSeeder::class,
        ]);
    }

    /**
     * Test fetching all event categories.
     */
    public function test_can_fetch_all_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                    ],
                ],
                'meta',
            ])
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /**
     * Test fetching public events catalog.
     * Verifies pagination and only PUBLISHED events are returned (DRAFT is excluded).
     */
    public function test_can_fetch_public_events_catalog_excluding_drafts(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'category',
                        'activity_type',
                        'organizer_name',
                        'poster_url',
                        'start_time',
                        'end_time',
                        'location',
                        'quota_total',
                        'quota_remaining',
                        'is_quota_full',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);

        // Expecting 3 published events from EventSeeder (Draft event must NOT be returned)
        $this->assertEquals(3, $response->json('meta.total'));
        $slugs = collect($response->json('data'))->pluck('slug')->toArray();
        $this->assertNotContains('draf-konser-internal-2026', $slugs);
    }

    /**
     * Test search filtering by title keyword.
     */
    public function test_can_filter_events_by_search_keyword(): void
    {
        $response = $this->getJson('/api/v1/events?search=artificial');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('seminar-nasional-ai-2026', $response->json('data.0.slug'));
    }

    /**
     * Test filtering events by category slug.
     */
    public function test_can_filter_events_by_category(): void
    {
        $response = $this->getJson('/api/v1/events?category=workshop');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('workshop-laravel-api-2026', $response->json('data.0.slug'));
    }

    /**
     * Test filtering events by activity type (COMMITTEE_RECRUITMENT).
     */
    public function test_can_filter_events_by_activity_type(): void
    {
        $response = $this->getJson('/api/v1/events?type=COMMITTEE_RECRUITMENT');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('oprec-panitia-ospek-2026', $response->json('data.0.slug'));
    }

    /**
     * Test fetching single event details by slug.
     */
    public function test_can_fetch_single_event_details(): void
    {
        $response = $this->getJson('/api/v1/events/seminar-nasional-ai-2026');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'description',
                    'activity_type',
                    'poster_url',
                    'status',
                    'start_time',
                    'end_time',
                    'location',
                    'quota_total',
                    'quota_remaining',
                    'is_quota_full',
                    'category' => [
                        'id',
                        'name',
                        'slug',
                    ],
                    'organizer' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'slug' => 'seminar-nasional-ai-2026',
                    'category' => [
                        'name' => 'Seminar',
                    ],
                    'organizer' => [
                        'name' => 'Sarah Nabila (LEM FTI UII)',
                    ],
                ],
            ]);
    }

    /**
     * Test fetching recruitment event details includes divisions list.
     */
    public function test_recruitment_event_includes_divisions(): void
    {
        $response = $this->getJson('/api/v1/events/oprec-panitia-ospek-2026');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'divisions' => [
                        '*' => [
                            'id',
                            'name',
                            'quota',
                            'description',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(4, $response->json('data.divisions'));
    }

    /**
     * Test requesting a non-existent or DRAFT event returns 404 envelope.
     */
    public function test_draft_or_non_existent_event_returns_404(): void
    {
        // 1. Non-existent slug
        $response1 = $this->getJson('/api/v1/events/slug-tidak-pernah-ada');
        $response1->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_NOT_FOUND',
            ]);

        // 2. Draft slug (Exists in DB but not PUBLISHED)
        $response2 = $this->getJson('/api/v1/events/draf-konser-internal-2026');
        $response2->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_NOT_FOUND',
            ]);
    }
}
