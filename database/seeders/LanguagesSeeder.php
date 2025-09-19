<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
			$languages = [
				['language' => 'javascript'],
				['language' => 'typescript'],
				['language' => 'php'],
				['language' => 'css'],
				['language' => 'markdown'],
				['language' => 'json'],
				['language' => 'python'],
				['language' => 'powershell'],
				['language' => 'html'],
				['language' => 'vue'],
			];
			foreach ($languages as $language) {
				Language::create($language);
			}
		
    }
}
