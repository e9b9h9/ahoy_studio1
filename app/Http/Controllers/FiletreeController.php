<?php

namespace App\Http\Controllers;

use App\Models\Filetree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FiletreeController extends Controller
{
    /**
     * Create a filetree from the specified path
     */
    public function store(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->path;
        
        // Normalize path separators for Windows/Linux compatibility
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Check if path exists
        if (!is_dir($path)) {
            return response()->json(['error' => "Directory does not exist: {$path}"], 400);
        }
        
        // Check if path is readable
        if (!is_readable($path)) {
            return response()->json(['error' => "Directory is not readable: {$path}"], 400);
        }

        // Extract project folder name (last part of path)
        $projectFolderName = basename($path);
        
        // Get the next count for this project folder name
        $count = Filetree::getNextCount($projectFolderName);
        
        try {
            // Start scanning from the root path
            $itemCount = $this->scanDirectory($path, null, $projectFolderName, $count);
            
            return response()->json([
                'message' => 'Filetree created successfully',
                'project_folder_name' => $projectFolderName,
                'count' => $count,
                'items_created' => $itemCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Filetree creation error: ' . $e->getMessage(), [
                'path' => $path,
                'project_folder_name' => $projectFolderName,
                'count' => $count,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Error creating filetree: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Recursively scan directory and create filetree entries
     */
    private function scanDirectory($currentPath, $parentId, $projectFolderName, $count)
    {
        $itemCount = 0;
        
        try {
            // Get all items in current directory
            $iterator = new \DirectoryIterator($currentPath);
            
            foreach ($iterator as $item) {
                if ($item->isDot()) {
                    continue;
                }
                
                // Skip hidden files and system files
                $name = $item->getFilename();
                if (str_starts_with($name, '.') && $name !== '.env') {
                    continue;
                }
                
                $isFolder = $item->isDir();
                $fullPath = $item->getPathname();
                
                // Normalize path separators
                $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
                
                \Log::info('Creating filetree entry', [
                    'name' => $name,
                    'path' => $fullPath,
                    'is_folder' => $isFolder,
                    'parent_id' => $parentId,
                    'project_folder_name' => $projectFolderName,
                    'count' => $count
                ]);
                
                // Create filetree entry
                $filetree = Filetree::create([
                    'path' => $fullPath,
                    'is_folder' => $isFolder,
                    'name' => $name,
                    'parent_id' => $parentId,
                    'project_folder_name' => $projectFolderName,
                    'project_folder_name_count' => $count
                ]);
                
                $itemCount++;
                
                // If it's a folder, recursively scan it (but skip common folders we don't want)
                if ($isFolder && !in_array($name, ['node_modules', '.git', 'vendor', '.vscode', '.idea'])) {
                    $subCount = $this->scanDirectory($fullPath, $filetree->id, $projectFolderName, $count);
                    $itemCount += $subCount;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with what we have
            \Log::error("Error scanning directory {$currentPath}: " . $e->getMessage());
            error_log("Error scanning directory {$currentPath}: " . $e->getMessage());
        }
        
        return $itemCount;
    }

    /**
     * Get filetree for a specific project
     */
    public function show($projectFolderName, $count = null)
    {
        $filetrees = Filetree::getProjectTree($projectFolderName, $count);
        
        return response()->json($filetrees);
    }

    /**
     * List all project folder names with their counts
     */
    public function index()
    {
        $projects = Filetree::select('project_folder_name', 'project_folder_name_count')
            ->distinct()
            ->orderBy('project_folder_name')
            ->orderBy('project_folder_name_count')
            ->get()
            ->groupBy('project_folder_name')
            ->map(function ($group) {
                return $group->pluck('project_folder_name_count')->toArray();
            });
        
        return response()->json($projects);
    }
}