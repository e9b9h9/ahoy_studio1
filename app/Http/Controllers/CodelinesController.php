<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TempCodeline;
use App\Models\Language;
use App\Models\LanguageExtension;
use App\Models\Variable;
use App\Services\CodelineProcessingService;
use App\Services\CreateCodeblocks;
use App\Services\Rules\MultilineCommentRule;
use App\Services\Rules\ConsecutiveCommentRule;
use App\Services\Rules\EmbeddedCommentRule;
use App\Services\Rules\VueLanguageRule;
use App\Services\Rules\OpenerCloserRule;
use App\Services\Rules\VuePurposeKeyRule;
use App\Services\Rules\VueVariableRule;
use App\Services\Rules\CodelineLevelRule;
use Illuminate\Support\Facades\Log;

class CodelinesController extends Controller
{
    public function process(Request $request)
    {
        $validated = $request->validate([
            'file_path' => 'required|string'
        ]);

        $fullPath = $validated['file_path'];
        
        // Check if file exists and is readable
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return response()->json([
                'success' => false, 
                'message' => 'File not found or not readable'
            ], 404);
        }

        // Read file lines
        $lines = file($fullPath, FILE_IGNORE_NEW_LINES);
        
        // Get file extension
        $fileExtension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        // Look up language by extension
        $languageExtension = LanguageExtension::where('extension', $fileExtension)->first();
        $languageId = $languageExtension ? $languageExtension->language_id : null;
        
        // Process lines through service with rules
        $processingService = new CodelineProcessingService();
        // OpenerCloserRule runs first to identify structural elements
        $processingService->addRule(new OpenerCloserRule());
        // VueLanguageRule runs second to establish language context
        $processingService->addRule(new VueLanguageRule());
        // VuePurposeKeyRule runs third to identify purpose based on language context
        $processingService->addRule(new VuePurposeKeyRule());
        // VueVariableRule runs fourth to extract variables based on purpose
        $processingService->addRule(new VueVariableRule());
        // CodelineLevelRule runs fifth to calculate hierarchical levels
        $processingService->addRule(new CodelineLevelRule());
        // Comment rules process and combine/skip lines as needed
        $processingService->addRule(new MultilineCommentRule());
        $processingService->addRule(new ConsecutiveCommentRule());
        $processingService->addRule(new EmbeddedCommentRule());
        
        $context = [
            'file_path' => $fullPath,
            'file_extension' => $fileExtension,
            'language_id' => $languageId
        ];
        
        $codelines = $processingService->processLines($lines, $context);
        
        // Scan for variable usage across all processed codelines
        $codelines = VueVariableRule::scanVariableUsage($codelines);
        
        // Log processed data before database insertion
        Log::info('Processed codelines data:', [
            'file_path' => $fullPath,
            'file_extension' => $fileExtension,
            'language_id' => $languageId,
            'total_lines' => count($codelines),
            'sample_lines' => array_slice($codelines, 0, 5), // First 5 lines as sample
            'processed_data' => $codelines
        ]);

        // Process variables and prepare codelines for insertion
        $cleanedCodelines = collect($codelines)
    ->filter(fn($line) => !empty(trim($line['codeline'] ?? '')))
    ->filter(fn($line) => preg_match('/\S/', $line['codeline'] ?? ''))
    ->map(function($line) {
        // Extract variable names array from the variables data
        $variableNames = [];
        if (isset($line['variables']) && is_array($line['variables'])) {
            foreach ($line['variables'] as $variable) {
                if (isset($variable['name'])) {
                    $variableNames[] = $variable['name'];
                    
                    // Save each variable to the variables table
                    Variable::firstOrCreate(
                        ['variable' => $variable['name']],
                        [
                            'type' => $variable['type'] ?? null,
                            'transformations' => json_encode([]),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }
        }
        
        return [
            'codeline' => $line['codeline'],
            'comment' => $line['comment'] ?? null,
            'variables' => !empty($variableNames) ? json_encode($variableNames) : null,
            'purpose_key' => $line['purpose_key'] ?? null,
            'file_location' => $line['file_location'] ?? null,
            'is_opener' => $line['is_opener'] ?? null,
            'is_closer' => $line['is_closer'] ?? null,
            'language_id' => $line['language_id'] ?? null,
            'master_codeline_id' => $line['master_codeline_id'] ?? null,
            'level' => $line['level'] ?? null,
            'line_number' => $line['line_number'] ?? null,
            'created_at' => $line['created_at'] ?? now(),
            'updated_at' => $line['updated_at'] ?? now()
        ];
    })
    ->toArray();
        
        // Bulk insert to temp_codelines table
        if (!empty($cleanedCodelines)) {
            TempCodeline::insert($cleanedCodelines);
        }

        // Create master codelines first (this assigns master_codeline_id to temp_codelines)
        $masterCodelineController = new MasterCodelineController();
        $masterCodelineResult = $masterCodelineController->create($request);
        $masterCodelineData = $masterCodelineResult->getData(true);
        
        Log::info('Master codeline creation completed', [
            'new_master_codelines' => $masterCodelineData['new_master_codelines'],
            'linked_to_existing' => $masterCodelineData['linked_to_existing'],
            'total_processed' => $masterCodelineData['total_processed']
        ]);

        // THEN create codeblocks after master codelines are created
        $codeblockService = new CreateCodeblocks();
        $codeblockResult = $codeblockService->process();
        
        Log::info('Codeblock creation completed', [
            'codeblocks_created' => $codeblockResult['codeblocks_created'],
            'total_lines_processed' => $codeblockResult['total_lines_processed']
        ]);

        return response()->json([
            'success' => true,
            'lines_processed' => count($codelines),
            'codeblocks_created' => $codeblockResult['codeblocks_created'],
            'master_codelines' => [
                'new' => $masterCodelineData['new_master_codelines'],
                'linked' => $masterCodelineData['linked_to_existing'],
                'total' => $masterCodelineData['total_processed']
            ]
        ]);
    }
}