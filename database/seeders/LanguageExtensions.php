<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\LanguageExtension;
use Illuminate\Database\Seeder;

class LanguageExtensions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languageExtensions = [
            ['extension' => 'js', 'language_id' => 1],
            ['extension' => 'ts', 'language_id' => 2],
            ['extension' => 'php', 'language_id' => 3],
            ['extension' => 'css', 'language_id' => 4],
            ['extension' => 'md', 'language_id' => 5],
						['extension' => 'json', 'language_id' => 6],
						['extension' => 'py', 'language_id' => 7],
						['extension' => 'ps1', 'language_id' => 8],
						['extension' => 'html', 'language_id' => 9],
						['extension' => 'vue', 'language_id' => 10],
        ];
        foreach ($languageExtensions as $languageExtension) {
            LanguageExtension::create($languageExtension);
        }
    }
}
