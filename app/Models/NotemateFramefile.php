<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotemateFramefile extends Model
{
    protected $table = 'notemate_framefiles';
    
    protected $fillable = [
        'framefile_name',
        'framefile_path',
        'framefile_extension',
        'is_framefolder',
        'parent_id'
    ];

    protected $casts = [
        'is_framefolder' => 'boolean'
    ];
}