<?php

namespace App\Http\Controllers;

use App\Services\CreateCodeblocks;
use Illuminate\Http\Request;

class CodeblocksController extends Controller
{
    public function create(Request $request)
    {
        $service = new CreateCodeblocks();
        $result = $service->process();
        
        return response()->json([
            'success' => true,
            'codeblocks_created' => $result['codeblocks_created'],
            'total_lines_processed' => $result['total_lines_processed']
        ]);
    }
}