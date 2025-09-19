<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCodeline extends Model
{
    protected $table = 'master_codelines';
    
    protected $fillable = [
        'codeline',
        'comment',
        'variables',
        'purpose_key',
        'file_location',
        'is_opener',
        'is_closer',
        'language_id'
    ];
    
    protected $casts = [
        'is_opener' => 'boolean',
        'is_closer' => 'boolean'
    ];
    
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
    
    public function tempCodelines()
    {
        return $this->hasMany(TempCodeline::class, 'master_codeline_id');
    }
}