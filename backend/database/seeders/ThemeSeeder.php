<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'key' => 'theme_a',
                'name' => 'Minimal Elegance',
                'preview_url' => 'https://assets.webintoapp.test/themes/theme_a_preview.png',
                'schema_version' => 1,
                'defaults' => [
                    'layout' => 'theme_a',
                    'colors' => [
                        'primary' => '#111827',
                        'secondary' => '#4B5563',
                        'background' => '#FFFFFF',
                        'surface' => '#F9FAFB',
                        'text' => '#111827',
                    ],
                    'radius' => 8,
                    'font' => 'Inter',
                    'nav' => 'bottom',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'theme_b',
                'name' => 'Boutique Glamour',
                'preview_url' => 'https://assets.webintoapp.test/themes/theme_b_preview.png',
                'schema_version' => 1,
                'defaults' => [
                    'layout' => 'theme_b',
                    'colors' => [
                        'primary' => '#E11D48',
                        'secondary' => '#BE185D',
                        'background' => '#FFF1F2',
                        'surface' => '#FFFFFF',
                        'text' => '#881337',
                    ],
                    'radius' => 16,
                    'font' => 'Poppins',
                    'nav' => 'bottom',
                ],
                'is_active' => true,
            ],
            [
                'key' => 'theme_c',
                'name' => 'Apex Dark Luxury',
                'preview_url' => 'https://assets.webintoapp.test/themes/theme_c_preview.png',
                'schema_version' => 1,
                'defaults' => [
                    'layout' => 'theme_c',
                    'colors' => [
                        'primary' => '#6366F1',
                        'secondary' => '#818CF8',
                        'background' => '#0F172A',
                        'surface' => '#1E293B',
                        'text' => '#F8FAFC',
                    ],
                    'radius' => 12,
                    'font' => 'Outfit',
                    'nav' => 'bottom',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($themes as $theme) {
            Theme::updateOrCreate(
                ['key' => $theme['key']],
                $theme
            );
        }
    }
}
