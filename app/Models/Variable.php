<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    protected $table = 'variables';
    
    protected $fillable = [
        'variable',
        'transformations',
        'type',
        'variable_id'
    ];
    
    protected $casts = [
        'transformations' => 'json'
    ];
}