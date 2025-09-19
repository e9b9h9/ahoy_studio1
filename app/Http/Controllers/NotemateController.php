<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotemateCodefolder;

class NotemateController extends Controller
{
    public function getCodefolders()
    {
        $codefolders = NotemateCodefolder::all();
        return response()->json($codefolders);
    }

    public function storeCodefolder(Request $request)
    {
        // Set all existing folders to not working
        NotemateCodefolder::query()->update(['is_working' => false]);
        
        // Create new folder with is_working = true
        NotemateCodefolder::create([
            'path' => $request->path,
            'is_working' => true,
        ]);

        return response()->json(['success' => true]);
    }
    
    public function setWorkingFolder(Request $request, $id)
    {
        // Set all folders to not working
        NotemateCodefolder::query()->update(['is_working' => false]);
        
        // Set the selected folder to working
        $codefolder = NotemateCodefolder::find($id);
        if ($codefolder) {
            $codefolder->is_working = true;
            $codefolder->save();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
    }



}