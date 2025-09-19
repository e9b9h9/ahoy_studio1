<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotemateSnippetFeature extends Model
{
    protected $table = 'notemate_snippetfeatures';
    
    protected $fillable = [
        'snippetfeature',
        'parent_id',
        'is_working'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NotemateSnippetFeature::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NotemateSnippetFeature::class, 'parent_id');
    }
}