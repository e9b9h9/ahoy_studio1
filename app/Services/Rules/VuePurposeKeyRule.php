<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class VuePurposeKeyRule implements CodelineRuleInterface
{
    public function apply(array $codelineData, array $context): array
    {
        $lineContent = $codelineData['codeline'] ?? '';
        $trimmed = trim($lineContent);
        $fileLocation = $codelineData['file_location'] ?? '';
        
        // Check for script and template tags themselves
        if (preg_match('/^<(script|template|\/script|\/template)/', $trimmed)) {
            $codelineData['purpose_key'] = 'page_setup';
            return $codelineData;
        }
        
        // Check for div tags (opening and closing, but not self-closing)
        if (preg_match('/^<div(?:\s+[^>]*)?>$/', $trimmed) || preg_match('/^<\/div>$/', $trimmed)) {
            $codelineData['purpose_key'] = 'page_structure';
            return $codelineData;
        }
        
        // Only process lines that are in script tags
        if (str_contains($fileLocation, '<script')) {
            
            // Check for import statements
            if (str_starts_with($trimmed, 'import ')) {
                $codelineData['purpose_key'] = 'import_statement';
                
                // Optional: Extract what's being imported for more detailed categorization
                if (preg_match('/import\s+(?:{[^}]+}|\*\s+as\s+\w+|\w+)\s+from\s+[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                    $module = $matches[1];
                    
                    // Categorize imports further based on source
                    if (str_starts_with($module, '@/') || str_starts_with($module, './') || str_starts_with($module, '../')) {
                        $codelineData['purpose_key'] = 'import_local';
                    } elseif (str_starts_with($module, 'vue')) {
                        $codelineData['purpose_key'] = 'import_vue';
                    } else {
                        $codelineData['purpose_key'] = 'import_external';
                    }
                } else {
                    // Default to generic import statement
                    $codelineData['purpose_key'] = 'import_statement';
                }
            }
            
            // Check for export statements
            elseif (str_starts_with($trimmed, 'export ')) {
                $codelineData['purpose_key'] = 'export_statement';
            }
            
            // Check for const/let/var declarations
            elseif (preg_match('/^(const|let|var)\s+/', $trimmed)) {
                // Check if it's a props definition
                if (preg_match('/^(const|let|var)\s+props\s*=/', $trimmed)) {
                    $codelineData['purpose_key'] = 'props_definition';
                } else {
                    $codelineData['purpose_key'] = 'variable_declaration';
                }
            }
            
            // Check for prop property definitions (lines like "id: {" or "name: String")
            elseif (preg_match('/^\s*([a-zA-Z_$][a-zA-Z0-9_$]*)\s*:\s*[{]/', $trimmed)) {
                $codelineData['purpose_key'] = 'prop_property';
            }
            
            // Check for function declarations
            elseif (preg_match('/^(function\s+\w+|const\s+\w+\s*=\s*(?:async\s+)?(?:\([^)]*\)|\w+)\s*=>)/', $trimmed)) {
                $codelineData['purpose_key'] = 'function_declaration';
            }
            
            // Check for reactive/ref/computed from Vue
            elseif (preg_match('/\b(ref|reactive|computed|watch|watchEffect|onMounted|onUnmounted|onUpdated)\s*\(/', $trimmed)) {
                $codelineData['purpose_key'] = 'vue_reactivity';
            }
            
            // Check for interface/type declarations (TypeScript)
            elseif (preg_match('/^(interface|type)\s+\w+/', $trimmed)) {
                $codelineData['purpose_key'] = 'type_definition';
            }
        }
        
        // Process template section items
        elseif ($fileLocation === '<template>') {
            
            // Check for v-if, v-for, v-show directives
            if (preg_match('/\bv-(if|for|show|else|else-if|model|on|bind|slot)\b/', $lineContent)) {
                $codelineData['purpose_key'] = 'vue_directive';
            }
            
            // Check for component tags (PascalCase or kebab-case custom components)
            elseif (preg_match('/<([A-Z][a-zA-Z0-9]+|[a-z]+(?:-[a-z]+)+)(?:\s|>|$)/', $trimmed)) {
                $codelineData['purpose_key'] = 'component_usage';
            }
            
            // Check for event handlers
            elseif (preg_match('/@(click|submit|input|change|focus|blur|keyup|keydown|mouseenter|mouseleave)/', $lineContent)) {
                $codelineData['purpose_key'] = 'event_handler';
            }
            
            // Check for class bindings
            elseif (preg_match('/:(class|style)=/', $lineContent)) {
                $codelineData['purpose_key'] = 'dynamic_binding';
            }
        }
        
        return $codelineData;
    }
}