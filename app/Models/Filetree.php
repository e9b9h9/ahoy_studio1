<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filetree extends Model
{
    protected $fillable = [
        'path',
        'is_folder',
        'name',
        'parent_id',
        'project_folder_name',
        'project_folder_name_count'
    ];

    protected $casts = [
        'is_folder' => 'boolean',
    ];

    /**
     * Get the next available count for a project folder name
     */
    public static function getNextCount($projectFolderName)
    {
        $maxCount = self::where('project_folder_name', $projectFolderName)
            ->max('project_folder_name_count');
        
        return $maxCount === null ? null : $maxCount + 1;
    }

    /**
     * Get all filetrees for a specific project folder name and count
     */
    public static function getProjectTree($projectFolderName, $count = null)
    {
        $query = self::where('project_folder_name', $projectFolderName);
        
        if ($count === null) {
            $query->whereNull('project_folder_name_count');
        } else {
            $query->where('project_folder_name_count', $count);
        }
        
        return $query->orderBy('is_folder', 'desc')
            ->orderBy('name')
            ->get();
    }
}