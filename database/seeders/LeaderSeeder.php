<?php

namespace Database\Seeders;

use App\Models\Leader;
use Illuminate\Database\Seeder;

/**
 * Seed the initial leadership roster. Photo paths point to public/images/foundation/leadership
 * so the public about page renders them via the asset() fallback used in the Livewire admin
 * (anything that doesn't start with 'http' is treated as a public asset path here, since the
 * roster pre-dates the upload flow).
 */
class LeaderSeeder extends Seeder
{
    public function run(): void
    {
        $roster = [
            ['Victoria Nyamadi',       'Founder · Board Chair',  'images/foundation/leadership/victoria-nyamadi.jpg'],
            ['Bless Amago',            'Board of Directors',     'images/foundation/leadership/bless-amago.jpg'],
            ['Sarah Nyamai',           'Board of Directors',     'images/foundation/leadership/sarah-nyamai.jpg'],
            ['Gladys Kplorla Nyamadi', 'Board of Directors',     'images/foundation/leadership/gladys-nyamadi.jpg'],
            ['R.E. Amedzekor',         'Board of Directors',     'images/foundation/leadership/re-amedzekor.jpg'],
            ['Daniel Gbetodeme',       'Board of Directors',     'images/foundation/leadership/daniel-gbetodeme.jpg'],
            ['Togbui Gbe',             'Board of Directors',     'images/foundation/leadership/togbui-gbe.jpg'],
            ['Ama Baffoe',             'Board of Directors',     'images/foundation/leadership/ama-baffoe.jpg'],
            ['Sabrina Nyamadi',        'Board of Directors',     'images/foundation/leadership/sabrina-nyamadi.jpg'],
            ['Prof Lebene',            'Board of Advisors',      'images/foundation/leadership/prof-lebene.jpg'],
            ['Dr. Kaledzi',            'Board of Advisors',      'images/foundation/leadership/dr-kaledzi.jpg'],
        ];

        foreach ($roster as $i => [$name, $role, $photo]) {
            Leader::query()->updateOrCreate(
                ['name' => $name],
                [
                    'role' => $role,
                    'photo_path' => $photo,
                    'is_published' => true,
                    'sort' => $i,
                ],
            );
        }
    }
}
