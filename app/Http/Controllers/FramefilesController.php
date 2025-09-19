<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotemateFramefile;

class FramefilesController extends Controller
{
    public function process(Request $request)
    {
        $validated = $request->validate([
            'file_path' => 'required|string',
            'file_name' => 'required|string'
        ]);

        // Extract file extension
        $extension = pathinfo($validated['file_name'], PATHINFO_EXTENSION);
        
        // Create framefile record
        $framefile = NotemateFramefile::create([
            'framefile_name' => $validated['file_name'],
            'framefile_path' => $validated['file_path'],
            'framefile_extension' => $extension ?: null,
            'is_framefolder' => false,
            'parent_id' => null
        ]);

        return response()->json([
            'success' => true,
            'framefile_id' => $framefile->id
        ]);
    }
}