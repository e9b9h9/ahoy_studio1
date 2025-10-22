<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TempCodeline;
use App\Models\Language;
use App\Models\LanguageExtension;
use App\Models\Variable;
use App\Services\CodelineProcessingService;
use App\Services\CodelineConnectionService;
// COMMENTED OUT: Codeblock creation service
// use App\Services\CreateCodeblocks;
use App\Services\ModuleImportService;
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

        // Track module imports for alert
        $moduleImports = [];
        
        // Process variables and prepare codelines for insertion
        $cleanedCodelines = collect($codelines)
    ->filter(fn($line) => !empty(trim($line['codeline'] ?? '')))
    ->filter(fn($line) => preg_match('/\S/', $line['codeline'] ?? ''))
    ->map(function($line) use (&$moduleImports) {
        // Extract variable names array from the variables data
        $variableNames = [];
        if (isset($line['variables']) && is_array($line['variables'])) {
            foreach ($line['variables'] as $variable) {
                if (isset($variable['name'])) {
                    $variableNames[] = $variable['name'];
                    
                    // Track module imports for alert
                    if (isset($variable['type']) && $variable['type'] === 'module_import') {
                        $moduleImports[] = [
                            'module' => $variable['name'],
                            'line_number' => $line['line_number'] ?? null,
                            'codeline' => $line['codeline'] ?? ''
                        ];
                    }
                    
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
            'total_processed' => $masterCodelineData['total_processed'],
            'variable_links_created' => $masterCodelineData['variable_links_created'] ?? 0
        ]);

        // Create connections between codelines that share variables
        try {
            $connectionService = new CodelineConnectionService();
            $connectionStats = $connectionService->createVariableConnections($codelines);
            
            Log::info('Codeline connection creation completed', $connectionStats);
        } catch (\Exception $e) {
            Log::error('Failed to create codeline connections', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Set default stats so processing can continue
            $connectionStats = [
                'connections_created' => 0,
                'variables_processed' => 0,
                'codelines_analyzed' => count($codelines),
                'shared_variables' => [],
                'errors' => ['Connection service failed: ' . $e->getMessage()]
            ];
        }

        // COMMENTED OUT: Codeblock creation logic
        // THEN create codeblocks after master codelines are created
        // $codeblockService = new CreateCodeblocks();
        // $codeblockResult = $codeblockService->process();
        
        // Log::info('Codeblock creation completed', [
        //     'codeblocks_created' => $codeblockResult['codeblocks_created'],
        //     'total_lines_processed' => $codeblockResult['total_lines_processed']
        // ]);
        
        // Set default values for codeblock results since we're not running it
        $codeblockResult = [
            'codeblocks_created' => 0,
            'total_lines_processed' => 0
        ];
        
        // Alert about module imports
        ModuleImportService::alertModuleImports($fullPath, $moduleImports);

        return response()->json([
            'success' => true,
            'lines_processed' => count($codelines),
            'codeblocks_created' => $codeblockResult['codeblocks_created'],
            'master_codelines' => [
                'new' => $masterCodelineData['new_master_codelines'],
                'linked' => $masterCodelineData['linked_to_existing'],
                'total' => $masterCodelineData['total_processed']
            ],
            'variable_links_created' => $masterCodelineData['variable_links_created'] ?? 0,
            'codeline_connections' => $connectionStats,
            'module_imports' => !empty($moduleImports) ? [
                'alert' => true,
                'total' => count(collect($moduleImports)->unique('module')),
                'modules' => collect($moduleImports)->unique('module')->pluck('module')->sort()->values()->toArray(),
                'details' => collect($moduleImports)->unique('module')->map(function($import) {
                    return [
                        'module' => $import['module'],
                        'line' => $import['line_number']
                    ];
                })->sortBy('line')->values()->toArray()
            ] : ['alert' => false, 'total' => 0]
        ]);
    }

    /**
     * Get temp codelines for display in UI
     */
    public function getTempCodelines()
    {
        $codelines = TempCodeline::with('language')
            ->orderBy('line_number')
            ->get();

        return response()->json([
            'success' => true,
            'codelines' => $codelines,
            'total_count' => $codelines->count()
        ]);
    }

    /**
     * Get variables for highlighting (just the variable names and types)
     */
    public function getVariablesWithCodelines()
    {
        // Get all unique variables from the variables table
        $variables = \DB::table('variables')
            ->select(
                'variables.variable as variable_name',
                'variables.type as variable_type'
            )
            ->distinct()
            ->get()
            ->keyBy('variable_name');

        return response()->json([
            'success' => true,
            'variables' => $variables,
            'total_variables' => count($variables)
        ]);
    }

    /**
     * Add a new variable to the variables table
     */
    public function addVariable(Request $request)
    {
        try {
            \Log::info('Add variable request received', [
                'variable' => $request->input('variable'),
                'type' => $request->input('type'),
                'codeline_id' => $request->input('codeline_id')
            ]);

            $request->validate([
                'variable' => 'required|string|max:255',
                'type' => 'nullable|string|max:100',
                'codeline_id' => 'nullable|integer|exists:temp_codelines,id'
            ]);

            $variableName = $request->input('variable');
            $variableType = $request->input('type', 'user_added');
            $codelineId = $request->input('codeline_id');

            // Check if variable already exists
            $existingVariable = \DB::table('variables')
                ->where('variable', $variableName)
                ->first();

            if ($existingVariable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variable already exists in database',
                    'variable' => $existingVariable
                ], 409);
            }

            // Get next variable_id
            $maxVariableId = \DB::table('variables')->max('variable_id') ?: 0;

            // Add new variable
            $variableId = \DB::table('variables')->insertGetId([
                'variable' => $variableName,
                'type' => $variableType,
                'transformations' => json_encode([]),
                'variable_id' => $maxVariableId + 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            \Log::info('Variable added successfully', ['id' => $variableId, 'variable' => $variableName]);

            return response()->json([
                'success' => true,
                'message' => 'Variable added successfully',
                'variable' => [
                    'id' => $variableId,
                    'variable' => $variableName,
                    'type' => $variableType
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation failed for add variable', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error adding variable', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get codeline connection statistics
     */
    public function getConnectionStats()
    {
        $connectionService = new CodelineConnectionService();
        $stats = $connectionService->getConnectionStats();

        return response()->json([
            'success' => true,
            'connection_stats' => $stats
        ]);
    }

    /**
     * Clear all codeline connections (for reprocessing)
     */
    public function clearConnections()
    {
        $connectionService = new CodelineConnectionService();
        $deletedCount = $connectionService->clearAllConnections();

        return response()->json([
            'success' => true,
            'message' => 'All connections cleared',
            'deleted_count' => $deletedCount
        ]);
    }
}