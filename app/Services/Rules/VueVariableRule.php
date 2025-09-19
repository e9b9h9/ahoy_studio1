<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;
use Illuminate\Support\Facades\DB;

class VueVariableRule implements CodelineRuleInterface
{
    protected static $allVariables = [];
    protected static $allCodelines = [];
    
    public function apply(array $codelineData, array $context): array
    {
        $lineContent = $codelineData['codeline'] ?? '';
        $purposeKey = $codelineData['purpose_key'] ?? '';
        
        // Store codeline for later scanning
        static::$allCodelines[] = $codelineData;
        
        $variables = [];
        
        // Process different purpose keys
        if ($purposeKey === 'import_vue') {
            $variables = $this->extractImportVueVariables($lineContent);
        } elseif ($purposeKey === 'import_local') {
            $variables = $this->extractImportLocalVariables($lineContent);
        } elseif ($purposeKey === 'variable_declaration') {
            $variables = $this->extractVariableDeclarationVariables($lineContent);
        } elseif ($purposeKey === 'component_usage') {
            $variables = $this->extractComponentUsageVariables($lineContent);
        } elseif ($purposeKey === 'props_definition') {
            $variables = $this->extractPropsDefinitionVariables($lineContent);
        } elseif ($purposeKey === 'prop_property') {
            $variables = $this->extractPropPropertyVariables($lineContent);
        }
        
        // Collect all variables found for later scanning
        foreach ($variables as $variable) {
            if (isset($variable['name'])) {
                static::$allVariables[] = $variable['name'];
            }
        }
        
        // Only set variables if we found any
        if (!empty($variables)) {
            $codelineData['variables'] = $variables;
        }
        
        return $codelineData;
    }
    
    protected function extractImportVueVariables(string $line): array
    {
        $variables = [];
        
        // Extract variables from import { var1, var2 } from 'vue'
        if (preg_match('/import\s+\{\s*([^}]+)\s*\}/', $line, $matches)) {
            $imports = explode(',', $matches[1]);
            foreach ($imports as $import) {
                $varName = trim($import);
                if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*/', $varName, $varMatch)) {
                    $variables[] = [
                        'name' => $varMatch[0],
                        'type' => 'composition_api'
                    ];
                }
            }
        }
        
        return $variables;
    }
    
    protected function extractImportLocalVariables(string $line): array
    {
        $variables = [];
        
        // Check for named imports with curly braces: import { var1, var2 } from './file'
        if (preg_match('/import\s+\{\s*([^}]+)\s*\}\s+from\s+[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            $source = $matches[2];
            $imports = explode(',', $matches[1]);
            foreach ($imports as $import) {
                $varName = trim($import);
                if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*/', $varName, $varMatch)) {
                    $variables[] = [
                        'name' => $varMatch[0],
                        'type' => 'function_name',
                        'source' => $source
                    ];
                }
            }
        }
        // Check for default import: import VariableName from './path/to/file'
        elseif (preg_match('/import\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s+from\s+[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            $variables[] = [
                'name' => $matches[1],
                'type' => 'vue_component',
                'source' => $matches[2]
            ];
        }
        
        return $variables;
    }
    
    protected function extractVariableDeclarationVariables(string $line): array
    {
        $variables = [];
        
        // Check for object destructuring: const { var1, var2 } = something
        if (preg_match('/^(const|let|var)\s+\{\s*([^}]+)\s*\}\s*=/', $line, $matches)) {
            $destructuredVars = $matches[2];
            // Split by comma, colon, or period and extract variable names
            $parts = preg_split('/[,:.]/', $destructuredVars);
            foreach ($parts as $part) {
                $varName = trim($part);
                if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*/', $varName, $varMatch)) {
                    $variables[] = [
                        'name' => $varMatch[0],
                        'type' => 'variable_declaration'
                    ];
                }
            }
        }
        // Extract variable name from const/let/var declarations
        // Matches: const variableName = something
        elseif (preg_match('/^(const|let|var)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*(.+)/', $line, $matches)) {
            // Add the variable name
            $variables[] = [
                'name' => $matches[2],
                'type' => 'variable_declaration'
            ];
            
            // Extract variables from the right side of assignment
            $rightSide = $matches[3];
            
            // Check for computed(() => expression)
            if (preg_match('/computed\s*\(\s*\(\)\s*=>\s*(.+)\)/', $rightSide, $computedMatch)) {
                $variables[] = [
                    'name' => 'computed',
                    'type' => 'vue_reactivity'
                ];
                // Extract from the expression inside computed
                $expression = $computedMatch[1];
                $variables = array_merge($variables, $this->extractVariablesFromExpression($expression));
            }
            // Otherwise extract variables from the whole right side
            else {
                $variables = array_merge($variables, $this->extractVariablesFromExpression($rightSide));
            }
        }
        
        return $variables;
    }
    
    protected function extractVariablesFromExpression(string $expression): array
    {
        $variables = [];
        
        // Extract all identifiers that look like variables or method calls
        // This pattern finds word.word.word() chains and standalone words
        if (preg_match_all('/([a-zA-Z_$][a-zA-Z0-9_$]*(?:\.[a-zA-Z_$][a-zA-Z0-9_$]*)*)\s*(?:\(|$|[^\w])/u', $expression, $matches)) {
            foreach ($matches[1] as $match) {
                // Split by dot to get each part
                $parts = explode('.', $match);
                foreach ($parts as $part) {
                    if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $part)) {
                        $variables[] = [
                            'name' => $part,
                            'type' => 'expression_variable'
                        ];
                    }
                }
            }
        }
        
        return $variables;
    }
    
    protected function extractComponentUsageVariables(string $line): array
    {
        $variables = [];
        
        // Extract component name after <
        if (preg_match('/<([a-zA-Z_$][a-zA-Z0-9_$-]*)/', $line, $matches)) {
            $variables[] = [
                'name' => $matches[1],
                'type' => 'component_usage'
            ];
        }
        
        // Extract variables after @ and : (before equal sign)
        if (preg_match_all('/[@:]([a-zA-Z_$][a-zA-Z0-9_$-]*)(?=\s*=)/', $line, $matches)) {
            foreach ($matches[1] as $varName) {
                $variables[] = [
                    'name' => $varName,
                    'type' => 'component_usage'
                ];
            }
        }
        
        // Extract variables from strings separated by dots
        if (preg_match_all('/"([^"]+)"/', $line, $stringMatches)) {
            foreach ($stringMatches[1] as $stringContent) {
                $parts = explode('.', $stringContent);
                foreach ($parts as $part) {
                    $varName = trim($part);
                    if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $varName)) {
                        $variables[] = [
                            'name' => $varName,
                            'type' => 'component_usage'
                        ];
                    }
                }
            }
        }
        
        return $variables;
    }
    
    protected function extractPropsDefinitionVariables(string $line): array
    {
        $variables = [];
        
        // Always add 'props' variable for props definitions
        $variables[] = [
            'name' => 'props',
            'type' => 'props_definition'
        ];
        
        // Check if defineProps is used
        if (str_contains($line, 'defineProps')) {
            $variables[] = [
                'name' => 'defineProps',
                'type' => 'props_definition'
            ];
        }
        
        return $variables;
    }
    
    protected function extractPropPropertyVariables(string $line): array
    {
        $variables = [];
        
        // Extract prop name from lines like "id: {" or "name: {"
        if (preg_match('/^\s*([a-zA-Z_$][a-zA-Z0-9_$]*)\s*:\s*[{]/', $line, $matches)) {
            $variables[] = [
                'name' => $matches[1],
                'type' => 'prop_property'
            ];
        }
        
        return $variables;
    }
    
    /**
     * Scan all codelines for variable usage after processing is complete
     */
    public static function scanVariableUsage(array $processedCodelines = null): array
    {
        // Use provided codelines or fall back to stored ones
        $codelinesToScan = $processedCodelines ?? static::$allCodelines;
        $updatedCodelines = [];
        $uniqueVariables = array_unique(static::$allVariables);
        
        foreach ($codelinesToScan as $codelineData) {
            $lineContent = $codelineData['codeline'] ?? '';
            $foundVariables = [];
            
            // Get existing variable names to avoid duplicates
            $existingVariables = $codelineData['variables'] ?? [];
            $existingVariableNames = array_column($existingVariables, 'name');
            
            // Scan for each unique variable in this codeline
            foreach ($uniqueVariables as $variable) {
                // Skip if variable is already in the existing variables
                if (in_array($variable, $existingVariableNames)) {
                    continue;
                }
                
                if (static::isVariableUsedInLine($variable, $lineContent)) {
                    $foundVariables[] = [
                        'name' => $variable,
                        'type' => 'variable_usage'
                    ];
                }
            }
            
            // Add found variables to existing variables array
            if (!empty($foundVariables)) {
                $allVariables = array_merge($existingVariables, $foundVariables);
                $codelineData['variables'] = $allVariables;
            }
            
            $updatedCodelines[] = $codelineData;
        }
        
        // Create codeline relationships based on shared variables
        static::createCodelineRelationships($updatedCodelines);
        
        // Reset static arrays for next processing run
        static::$allVariables = [];
        static::$allCodelines = [];
        
        return $updatedCodelines;
    }
    
    /**
     * Check if a variable is used in a line using word boundaries
     */
    protected static function isVariableUsedInLine(string $variable, string $line): bool
    {
        // Use word boundaries to ensure we match complete words only
        // \b ensures we don't match partial words like 'id' in 'identification'
        $pattern = '/\b' . preg_quote($variable, '/') . '\b/';
        
        return preg_match($pattern, $line) === 1;
    }
    
    /**
     * Create relationships between codelines that share variables
     */
    protected static function createCodelineRelationships(array $codelines): void
    {
        // Group codelines by variable names
        $variableToCodelines = [];
        
        foreach ($codelines as $codelineData) {
            $masterCodelineId = $codelineData['master_codeline_id'] ?? null;
            $lineNumber = $codelineData['line_number'] ?? null;
            $variables = $codelineData['variables'] ?? [];
            
            // Skip if no master codeline ID or line number
            if (!$masterCodelineId || $lineNumber === null) {
                continue;
            }
            
            // Group by variable name
            foreach ($variables as $variable) {
                $variableName = $variable['name'] ?? '';
                if (!empty($variableName)) {
                    if (!isset($variableToCodelines[$variableName])) {
                        $variableToCodelines[$variableName] = [];
                    }
                    
                    $variableToCodelines[$variableName][] = [
                        'master_codeline_id' => $masterCodelineId,
                        'line_number' => $lineNumber
                    ];
                }
            }
        }
        
        // Create relationships for variables that appear in multiple codelines
        $relationships = [];
        
        foreach ($variableToCodelines as $variableName => $codelinesList) {
            // Only create relationships if variable appears in 2+ codelines
            if (count($codelinesList) < 2) {
                continue;
            }
            
            // Sort by line number to establish order (lower line number = provides)
            usort($codelinesList, function($a, $b) {
                return $a['line_number'] <=> $b['line_number'];
            });
            
            // Create relationships: first codeline provides, others require
            $providesCodeline = $codelinesList[0];
            
            for ($i = 1; $i < count($codelinesList); $i++) {
                $requiresCodeline = $codelinesList[$i];
                
                $relationships[] = [
                    'requires_codeline_id' => $requiresCodeline['master_codeline_id'],
                    'provides_codeline_id' => $providesCodeline['master_codeline_id'],
                    'variable_name' => $variableName,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        // Bulk insert relationships
        if (!empty($relationships)) {
            // Remove any existing relationships first
            DB::table('codeline_codeline')->truncate();
            
            // Insert new relationships
            DB::table('codeline_codeline')->insert($relationships);
        }
    }
}