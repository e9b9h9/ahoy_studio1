<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotemateCodefolder extends Model
{
    protected $table = 'notemate_codefolders';
    protected $fillable = ['path', 'is_working'];

}
