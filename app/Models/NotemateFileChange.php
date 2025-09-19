<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotemateFileChange extends Model
{
    protected $fillable = [
        'codefolder_id',
        'file_path',
        'file_name',
        'folder_path',
        'change_type',
        'detected_at'
    ];

    protected $casts = [
        'detected_at' => 'datetime'
    ];

    public function codefolder(): BelongsTo
    {
        return $this->belongsTo(NotemateCodefolder::class, 'codefolder_id');
    }
}