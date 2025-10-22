<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ModuleImportService
{
    /**
     * Alert about module imports found during processing
     */
    public static function alertModuleImports($filePath, array $moduleImports)
    {
        if (empty($moduleImports)) {
            return;
        }
        
        // Remove duplicates and sort by line number
        $uniqueModules = collect($moduleImports)
            ->unique('module')
            ->sortBy('line_number')
            ->values()
            ->toArray();
        
        // Create detailed alert
        Log::alert('🚨 MODULE IMPORTS DETECTED', [
            'file_path' => $filePath,
            'total_imports' => count($uniqueModules),
            'modules' => array_map(function($import) {
                return [
                    'module' => $import['module'],
                    'line' => $import['line_number'],
                    'context' => substr($import['codeline'], 0, 100)
                ];
            }, $uniqueModules)
        ]);
        
        // Pretty formatted console output
        Log::info("╔══════════════════════════════════════════════════════════════╗");
        Log::info("║ MODULE IMPORTS FOUND IN: " . basename($filePath));
        Log::info("╠══════════════════════════════════════════════════════════════╣");
        
        foreach ($uniqueModules as $import) {
            Log::info(sprintf(
                "║ 📦 Line %4d: %s",
                $import['line_number'],
                $import['module']
            ));
        }
        
        Log::info("╠══════════════════════════════════════════════════════════════╣");
        Log::info("║ Total Unique Modules: " . count($uniqueModules));
        Log::info("╚══════════════════════════════════════════════════════════════╝");
        
        // Summary
        Log::warning("📊 Module Import Summary:", [
            'file' => $filePath,
            'total_unique_modules' => count($uniqueModules),
            'modules' => collect($uniqueModules)->pluck('module')->sort()->values()->toArray()
        ]);
    }
}