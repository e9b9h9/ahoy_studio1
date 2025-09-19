<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotemateFileChange extends Model
{
    protected $fillable = [
        'codefeature',
        'parent_id'
    ];



  
}