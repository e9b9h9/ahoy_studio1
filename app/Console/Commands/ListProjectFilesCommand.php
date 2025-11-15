<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListProjectFilesCommand extends Command
{
    protected $signature = 'files:list {--extension= : Filter by file extension (php, vue, js)} {--sort : Sort files by size (largest first)}';
    
    protected $description = 'List all project files excluding vendor, tests, storage, public, node_modules, config, bootstrap, and .vscode folders';
    
    private static string $printerTrayPath = 'PrinterTray';

    public function handle()
    {
        // Use Windows path since you're running on Windows
        $projectPath = 'C:\Users\emmah\Herd\ahoy_studio1';
        
        $excludedFolders = [
            'vendor',
            'tests', 
            'storage',
            'public',
            'node_modules',
            'config',
            'bootstrap',
            '.vscode'
        ];
        
        // Get file extension filter if provided
        $extension = $this->option('extension');
        $sortBySize = $this->option('sort');
        
        $this->info('Scanning project files in: ' . $projectPath);
        $this->info('Excluding folders: ' . implode(', ', $excludedFolders));
        if ($extension) {
            $this->info('Filtering for: .' . $extension . ' files only');
        }
        if ($sortBySize) {
            $this->info('Sorting by file size (largest first)');
        }
        $this->line('');
        
        $files = $this->getProjectFiles($projectPath, $excludedFolders, $extension, $sortBySize);
        
        $this->info('Found ' . count($files) . ' files:');
        $this->line('');
        
        // Display files in console
        foreach ($files as $file) {
            if ($sortBySize && is_array($file)) {
                $this->line($file['path']);
            } else {
                $this->line($file);
            }
        }
        
        // Save to PrinterTray
        $filename = 'project_files_list';
        if ($extension) {
            $filename .= '_' . $extension;
        }
        if ($sortBySize) {
            $filename .= '_sorted_by_size';
        }
        
        // Prepare data for saving
        $dataToSave = $sortBySize ? array_map(function($file) {
            return is_array($file) ? $file['path'] : $file;
        }, $files) : $files;
        
        // Save the file list
        $savedPath = self::save($dataToSave, $filename);
        
        if ($savedPath) {
            $this->line('');
            $this->info('File list saved to: ' . $savedPath);
            $this->info('Contains ' . count($files) . ' file paths');
        } else {
            $this->error('Failed to save file list to PrinterTray');
        }
        
        return 0;
    }
    
    private function getProjectFiles(string $basePath, array $excludedFolders, ?string $extension = null, bool $sortBySize = false): array
    {
        $files = [];
        
        if (!File::exists($basePath)) {
            return $files;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            // Normalize path separators to forward slashes
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $normalizedBasePath = str_replace('\\', '/', $basePath);
            
            // Get relative path
            $relativePath = str_replace($normalizedBasePath . '/', '', $fullPath);
            $pathParts = explode('/', $relativePath);
            
            // Check if file is in excluded folder
            $isExcluded = false;
            foreach ($excludedFolders as $excludedFolder) {
                if (in_array($excludedFolder, $pathParts)) {
                    $isExcluded = true;
                    break;
                }
            }
            
            // Only include files (not directories) that are not excluded
            if ($file->isFile() && !$isExcluded) {
                // Check file extension if extension filter is specified
                if ($extension) {
                    $fileExtension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                    if (strtolower($fileExtension) === strtolower($extension)) {
                        if ($sortBySize) {
                            $files[] = [
                                'path' => $fullPath,
                                'size' => $file->getSize()
                            ];
                        } else {
                            $files[] = $fullPath;
                        }
                    }
                } else {
                    if ($sortBySize) {
                        $files[] = [
                            'path' => $fullPath,
                            'size' => $file->getSize()
                        ];
                    } else {
                        $files[] = $fullPath;
                    }
                }
            }
        }
        
        if ($sortBySize) {
            // Sort by file size (largest first)
            usort($files, function($a, $b) {
                return $b['size'] <=> $a['size'];
            });
        } else {
            sort($files);
        }
        
        return $files;
    }
    
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

				public static function save($data, string $baseFilename, ?string $suffix = null): string|false
    {
        self::ensureDirectoryExists();
        
        $timestamp = date('Y-m-d_His');
        $filename = $baseFilename;
        
        if ($suffix) {
            $filename .= '_' . $suffix;
        }
        
        $filename .= '_' . $timestamp;
        
        // Determine format based on data type
        if (is_array($data) || is_object($data)) {
            $filename .= '.json';
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $filename .= '.txt';
            $content = (string) $data;
        }
        
        $fullPath = self::$printerTrayPath . DIRECTORY_SEPARATOR . $filename;
        
        if (File::put($fullPath, $content) !== false) {
            return $fullPath;
        }
        
        return false;
    }
    
    /**
     * Save multiple datasets as separate files
     * 
     * @param array $datasets Array of datasets with keys as suffixes
     * @param string $baseFilename Base name for all output files
     * @return array Array of saved file paths
     */
    public static function saveMultiple(array $datasets, string $baseFilename): array
    {
        $savedFiles = [];
        
        foreach ($datasets as $suffix => $data) {
            $path = self::save($data, $baseFilename, $suffix);
            if ($path) {
                $savedFiles[$suffix] = $path;
            }
        }
        
        return $savedFiles;
    }
    
    /**
     * Get the PrinterTray directory path
     */
    public static function getPath(): string
    {
        return self::$printerTrayPath;
    }
    
    /**
     * Ensure the PrinterTray directory exists
     */
    public static function ensureDirectoryExists(): void
    {
        if (!File::exists(self::$printerTrayPath)) {
            File::makeDirectory(self::$printerTrayPath, 0755, true);
        }
    }
    
    /**
     * Get info about data for logging
     */
    public static function getDataInfo($data): string
    {
        if (is_array($data)) {
            return 'array with ' . count($data) . ' items';
        } elseif (is_object($data)) {
            return 'object of type ' . get_class($data);
        } else {
            return 'string with ' . strlen((string)$data) . ' characters';
        }
    }
}