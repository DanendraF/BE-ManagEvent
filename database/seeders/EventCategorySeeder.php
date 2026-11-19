<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Seminar',
                'description' => 'Seminar akademik, talkshow inspiratif, dan simposium nasional.',
            ],
            [
                'name' => 'Workshop',
                'description' => 'Pelatihan keterampilan praktis, hands-on coding, dan sertifikasi keahlian.',
            ],
            [
                'name' => 'Lomba',
                'description' => 'Kompetisi mahasiswa, hackathon, debat, dan kejuaraan olahraga/seni.',
            ],
            [
                'name' => 'Konser & Festival',
                'description' => 'Pentas seni kampus, festival musik tahunan, dan perayaan milad ormawa.',
            ],
            [
                'name' => 'Open Recruitment',
                'description' => 'Perekrutan panitia dan staf kepanitiaan kegiatan kemahasiswaan kampus.',
            ],
        ];

        foreach ($categories as $cat) {
            EventCategory::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
