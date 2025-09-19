<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotemateSnippetFeature;

class SnippetFeaturesController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'snippetfeature' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:notemate_snippetfeatures,id',
        ]);

        // Set all existing features to not working
        NotemateSnippetFeature::query()->update(['is_working' => false]);
        
        // Create new feature and set it as working
        $snippetfeature = NotemateSnippetFeature::create($validated);
        $snippetfeature->is_working = true;
        $snippetfeature->save();

        return response()->json(['success' => true]);
    }

		public function setWorkingSnippetFeature(Request $request, $id)
    {
        // Set all folders to not working
        NotemateSnippetFeature::query()->update(['is_working' => false]);
        
        // Set the selected folder to working
        $snippetfeature = NotemateSnippetFeature::find($id);
        if ($snippetfeature) {
            $snippetfeature->is_working = true;
            $snippetfeature->save();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
    }

    public function index()
    {
        $features = NotemateSnippetFeature::orderBy('created_at', 'desc')->get();
        return response()->json($features);
    }
}