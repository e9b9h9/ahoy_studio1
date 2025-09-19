<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageExtension extends Model
{
    protected $table = 'language_extensions';

    protected $fillable = [
        'language_id',
				'extension'
    ];
}