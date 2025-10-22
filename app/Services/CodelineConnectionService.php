<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CodelineConnectionService
{
    /**
     * Create connections between codelines that share variables
     * Uses the variable_codeline table to find shared variables
     * 
     * @param array|null $processedCodelines Optional parameter for backwards compatibility
     * @return array Statistics about connections created
     */
    public function createVariableConnections($processedCodelines = null)
    {
        $stats = [
            'connections_created' => 0,
            'variables_processed' => 0,
            'codelines_analyzed' => 0,
            'shared_variables' => [],
            'errors' => []
        ];

        try {
            Log::info('Starting codeline connection analysis using variable_codeline table');

            // Get all variables that have multiple codelines associated with them
            $variableToCodelines = $this->getSharedVariablesFromDatabase();
            
            $stats['variables_processed'] = count($variableToCodelines);
            $stats['codelines_analyzed'] = array_sum(array_map('count', $variableToCodelines));

            // Create connections for each shared variable
            foreach ($variableToCodelines as $variableId => $codelineIds) {
                if (count($codelineIds) > 1) {
                    try {
                        $variableName = $this->getVariableName($variableId);
                        $connections = $this->createConnectionsForSharedVariable($variableId, $codelineIds, $variableName);
                        $stats['connections_created'] += $connections;
                        $stats['shared_variables'][$variableName] = count($codelineIds);
                    } catch (\Exception $e) {
                        $stats['errors'][] = "Error processing variable ID '{$variableId}': " . $e->getMessage();
                        Log::warning("Error processing variable connections", [
                            'variable_id' => $variableId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('Codeline connection analysis complete', $stats);
            
        } catch (\Exception $e) {
            $stats['errors'][] = "Fatal error in connection analysis: " . $e->getMessage();
            Log::error('Fatal error in codeline connection analysis', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $stats;
    }

    /**
     * Get variables that are shared across multiple codelines from the database
     * 
     * @return array Array where key is variable_id and value is array of codeline_ids
     */
    private function getSharedVariablesFromDatabase()
    {
        $variableToCodelines = [];

        // Query the variable_codeline table to group codelines by variable
        $variableCodelines = DB::table('variable_codeline')
            ->select('variable_id', 'codeline_id')
            ->get();

        foreach ($variableCodelines as $record) {
            $variableId = $record->variable_id;
            $codelineId = $record->codeline_id;

            if (!array_key_exists($variableId, $variableToCodelines)) {
                $variableToCodelines[$variableId] = [];
            }

            $variableToCodelines[$variableId][] = $codelineId;
        }

        // Only return variables that have multiple codelines
        return array_filter($variableToCodelines, function($codelineIds) {
            return count($codelineIds) > 1;
        });
    }

    /**
     * Get variable name by ID
     * 
     * @param int $variableId
     * @return string
     */
    private function getVariableName($variableId)
    {
        $variable = DB::table('variables')
            ->where('id', $variableId)
            ->first();

        return $variable ? $variable->name : "Variable#{$variableId}";
    }

    /**
     * Parse variables from different formats
     * 
     * @param mixed $variables
     * @return array
     */
    private function parseVariables($variables)
    {
        if (is_array($variables)) {
            // Handle array of variable objects (from database)
            $variableNames = [];
            foreach ($variables as $variable) {
                if (is_array($variable) && isset($variable['name'])) {
                    $variableNames[] = $variable['name'];
                } elseif (is_string($variable)) {
                    $variableNames[] = $variable;
                }
            }
            return $variableNames;
        }

        if (is_string($variables)) {
            // Try to parse as JSON first
            $jsonDecoded = json_decode($variables, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                // Recursively parse the decoded array
                return $this->parseVariables($jsonDecoded);
            }

            // Fall back to splitting by common separators
            return array_filter(
                array_map('trim', preg_split('/[,;|\s]+/', $variables)),
                function($var) {
                    return !empty($var) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $var);
                }
            );
        }

        return [];
    }

    /**
     * Create connections between codelines that share a variable
     * 
     * @param int $variableId
     * @param array $codelineIds Array of codeline IDs
     * @param string $variableName
     * @return int Number of connections created
     */
    private function createConnectionsForSharedVariable($variableId, $codelineIds, $variableName)
    {
        $connectionsCreated = 0;

        Log::info("Creating connections for variable: {$variableName} (ID: {$variableId})", ['codeline_count' => count($codelineIds)]);

        // Create connections between all pairs of codelines that share this variable
        for ($i = 0; $i < count($codelineIds); $i++) {
            for ($j = $i + 1; $j < count($codelineIds); $j++) {
                $id1 = $codelineIds[$i];
                $id2 = $codelineIds[$j];

                // Skip if IDs are not numeric
                if (!is_numeric($id1) || !is_numeric($id2)) {
                    continue;
                }

                // Create bidirectional connections
                $this->createConnection($id1, $id2, $variableName);
                $this->createConnection($id2, $id1, $variableName);
                
                $connectionsCreated += 2;
            }
        }

        return $connectionsCreated;
    }

    /**
     * Create a single connection between two codelines
     * 
     * @param int $codelineId
     * @param int $requiresCodelineId
     * @param string $variable The shared variable causing this connection
     * @return bool
     */
    private function createConnection($codelineId, $requiresCodelineId, $variable)
    {
        try {
            // Check if connection already exists
            $exists = DB::table('codeline_codeline')
                ->where('codeline_id', $codelineId)
                ->where('requires_codeline_id', $requiresCodelineId)
                ->exists();

            if ($exists) {
                return false; // Connection already exists
            }

            // Create the connection
            DB::table('codeline_codeline')->insert([
                'codeline_id' => $codelineId,
                'requires_codeline_id' => $requiresCodelineId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::debug("Created connection: {$codelineId} -> {$requiresCodelineId} (shared variable: {$variable})");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create connection: {$codelineId} -> {$requiresCodelineId}", [
                'variable' => $variable,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get connection statistics for reporting
     * 
     * @return array
     */
    public function getConnectionStats()
    {
        return [
            'total_connections' => DB::table('codeline_codeline')->count(),
            'unique_codelines_with_connections' => DB::table('codeline_codeline')
                ->select('codeline_id')
                ->distinct()
                ->count(),
            'most_connected_codelines' => DB::table('codeline_codeline')
                ->select('codeline_id', DB::raw('COUNT(*) as connection_count'))
                ->groupBy('codeline_id')
                ->orderBy('connection_count', 'desc')
                ->limit(10)
                ->get()
        ];
    }

    /**
     * Clear all connections (useful for reprocessing)
     * 
     * @return int Number of connections deleted
     */
    public function clearAllConnections()
    {
        $count = DB::table('codeline_codeline')->count();
        DB::table('codeline_codeline')->truncate();
        
        Log::info("Cleared all codeline connections", ['deleted_count' => $count]);
        
        return $count;
    }
}