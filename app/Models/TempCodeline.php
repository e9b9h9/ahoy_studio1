<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempCodeline extends Model
{
    protected $table = 'temp_codelines';

    protected $fillable = [
        'codeline',
        'comment',
        'language_id',
				'variables',
				'purpose_key',
				'file_location',
				'is_opener',
				'is_closer',
        'master_codeline_id',
        'level',
        'line_number'
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}