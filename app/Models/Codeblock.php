<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Codeblock extends Model
{
    protected $table = 'codeblocks';
    
    protected $fillable = [
        'codeblock'
    ];
    
    protected $casts = [
        'codeblock' => 'json'
    ];
    
    public function masterCodelines()
    {
        return $this->belongsToMany(MasterCodeline::class, 'codeline_codeblock', 'codeblock_id', 'codeline_id')
            ->withPivot('parent_codeline_id')
            ->withTimestamps();
    }
}