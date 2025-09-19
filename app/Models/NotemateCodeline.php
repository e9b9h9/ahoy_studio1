<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotemateCodeline extends Model
{
    protected $table = 'notemate_codelines';
    
    protected $fillable = [
        'codeline',
        'comment',
        'language'
    ];
}