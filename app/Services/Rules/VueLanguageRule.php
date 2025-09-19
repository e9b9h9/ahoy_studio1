<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class VueLanguageRule implements CodelineRuleInterface
{
    protected static $inScriptTag = false;
    protected static $scriptLang = null;
    protected static $inTemplateTag = false;
    protected static $templateDepth = 0;
    protected static $currentFile = null;
    
    public function apply(array $codelineData, array $context): array
    {
        $lineContent = $codelineData['codeline'] ?? '';
        $trimmed = trim($lineContent);
        
        // Reset state if we're processing a new file
        if (self::$currentFile !== ($context['file_path'] ?? null)) {
            self::$currentFile = $context['file_path'] ?? null;
            self::$inScriptTag = false;
            self::$scriptLang = null;
            self::$inTemplateTag = false;
            self::$templateDepth = 0;
        }
        
        // Check for script tag opening
        if (preg_match('/<script\s+setup(?:\s+lang="(\w+)")?\s*>/i', $trimmed, $matches)) {
            self::$inScriptTag = true;
            self::$scriptLang = isset($matches[1]) ? strtolower($matches[1]) : 'js';
            
            // Set language based on script lang attribute
            if (self::$scriptLang === 'ts' || self::$scriptLang === 'typescript') {
                $codelineData['language_id'] = 12; // vue-ts
                $codelineData['file_location'] = '<script setup lang="ts">';
            } else {
                $codelineData['language_id'] = 11; // vue-js
                $codelineData['file_location'] = '<script setup>';
            }
            
            return $codelineData;
        }
        
        // Check for script tag closing
        if (str_contains($trimmed, '</script>')) {
            // Set language for closing tag before resetting state
            if (self::$inScriptTag) {
                if (self::$scriptLang === 'ts' || self::$scriptLang === 'typescript') {
                    $codelineData['language_id'] = 12; // vue-ts
                    $codelineData['file_location'] = '<script setup lang="ts">';
                } else {
                    $codelineData['language_id'] = 11; // vue-js
                    $codelineData['file_location'] = '<script setup>';
                }
            }
            self::$inScriptTag = false;
            self::$scriptLang = null;
            return $codelineData;
        }
        
        // If we're inside a script tag, set appropriate language
        if (self::$inScriptTag) {
            if (self::$scriptLang === 'ts' || self::$scriptLang === 'typescript') {
                $codelineData['language_id'] = 12; // vue-ts
                $codelineData['file_location'] = '<script setup lang="ts">';
            } else {
                $codelineData['language_id'] = 11; // vue-js
                $codelineData['file_location'] = '<script setup>';
            }
            return $codelineData;
        }
        
        // Check for template tag opening
        if (preg_match('/<template\s*>/i', $trimmed)) {
            self::$inTemplateTag = true;
            self::$templateDepth = 0;
            $codelineData['language_id'] = 10; // vue
            $codelineData['file_location'] = '<template>';
            return $codelineData;
        }
        
        // Check for template tag closing
        if (str_contains($trimmed, '</template>')) {
            self::$inTemplateTag = false;
            self::$templateDepth = 0;
            $codelineData['language_id'] = 10; // vue
            $codelineData['file_location'] = '<template>';
            return $codelineData;
        }
        
        // If we're inside a template tag, check for div tags
        if (self::$inTemplateTag) {
            // Check specifically for div tags
            $hasOpenDiv = preg_match('/<div(?:\s+[^>]*)?\s*>/i', $lineContent);
            $hasCloseDiv = str_contains($lineContent, '</div>');
            
            // Only div tags get marked as tailwind
            if ($hasOpenDiv || $hasCloseDiv) {
                $codelineData['language_id'] = 13; // tailwind
            } else {
                // All other template content gets vue
                $codelineData['language_id'] = 10; // vue
            }
            
            $codelineData['file_location'] = '<template>';
            return $codelineData;
        }
        
        // Check for style tag (optional handling)
        if (preg_match('/<style(?:\s+[^>]*)?\s*>/i', $trimmed)) {
            // Could set CSS language if you have it in the languages table
            // For now, leave as is
            return $codelineData;
        }
        
        // For any other HTML-like tags outside of script blocks, mark as tailwind
        if (preg_match('/<[^>]+>/', $trimmed)) {
            $codelineData['language_id'] = 13; // tailwind
        }
        
        return $codelineData;
    }
}