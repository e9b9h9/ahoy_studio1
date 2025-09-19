<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotemateCodefolder;
use App\Models\NotemateFileChange;

class FileChangesController extends Controller
{
    public function index()
    {
        $activeFolder = NotemateCodefolder::where('is_working', true)->first();
        
        if (!$activeFolder) {
            return response()->json([]);
        }

        $fileChanges = NotemateFileChange::where('codefolder_id', $activeFolder->id)
            ->orderBy('detected_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($fileChanges);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codefolder_id' => 'required|exists:notemate_codefolders,id',
            'file_path' => 'required|string',
            'change_type' => 'required|in:new,modified,deleted',
            'detected_at' => 'required|date'
        ]);

        // Parse file path to get file name and folder path
        // Handle both forward slashes and backslashes
        $normalizedPath = str_replace('\\', '/', $validated['file_path']);
        $pathParts = explode('/', $normalizedPath);
        $fileName = array_pop($pathParts);
        $folderPath = count($pathParts) > 0 ? implode('/', $pathParts) : null;

        // Skip temporary files with pattern .tmp.###.##### (any numbers)
        if (preg_match('/\.tmp\.\d+\.\d+$/', $fileName)) {
            return response()->json(['success' => true, 'skipped' => 'temporary file']);
        }

        // Skip auto-generated actions files
        if (strpos($normalizedPath, 'resources/js/actions/') !== false) {
            return response()->json(['success' => true, 'skipped' => 'auto-generated actions file']);
        }

        // Skip auto-generated routes/api files
        if (strpos($normalizedPath, 'resources/js/routes/api/') !== false) {
            return response()->json(['success' => true, 'skipped' => 'auto-generated routes/api file']);
        }

        // Skip .vscode configuration files
        if (strpos($normalizedPath, '.vscode/') !== false) {
            return response()->json(['success' => true, 'skipped' => 'vscode configuration file']);
        }

        // Find existing change for this file
        $existingChange = NotemateFileChange::where('codefolder_id', $validated['codefolder_id'])
            ->where('file_path', $validated['file_path'])
            ->first();

        if ($existingChange) {
            // Priority: deleted > new/modified
            if ($validated['change_type'] === 'deleted') {
                // Always update to deleted (highest priority)
                $existingChange->update([
                    'change_type' => $validated['change_type'],
                    'detected_at' => $validated['detected_at']
                ]);
            } elseif ($existingChange->change_type !== 'deleted') {
                // Only update if existing is not deleted and not the same type
                if ($existingChange->change_type !== $validated['change_type']) {
                    // If existing is 'new', keep it as 'new' even for modifications
                    if ($existingChange->change_type === 'new' && $validated['change_type'] === 'modified') {
                        // Don't update - keep as 'new'
                    } else {
                        // Update the timestamp for other cases
                        $existingChange->update([
                            'change_type' => $validated['change_type'],
                            'detected_at' => $validated['detected_at']
                        ]);
                    }
                }
                // If same type, just update timestamp
                else {
                    $existingChange->update(['detected_at' => $validated['detected_at']]);
                }
            }
        } else {
            // No existing change, create new one
            NotemateFileChange::create([
                'codefolder_id' => $validated['codefolder_id'],
                'file_path' => $validated['file_path'],
                'file_name' => $fileName,
                'folder_path' => $folderPath,
                'change_type' => $validated['change_type'],
                'detected_at' => $validated['detected_at']
            ]);
        } 

        return response()->json(['success' => true]);
    }
}