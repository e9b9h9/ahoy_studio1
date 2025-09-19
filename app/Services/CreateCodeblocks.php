<?php

namespace App\Services;

use App\Models\TempCodeline;
use App\Models\Codeblock;
use App\Models\MasterCodeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateCodeblocks
{
    public function process()
    {
        // Get all temp_codelines ordered by line_number (fresh query)
        $codelines = TempCodeline::whereNotNull('master_codeline_id')
            ->orderBy('line_number')
            ->get();
        
        Log::info('CreateCodeblocks starting', [
            'total_codelines_found' => $codelines->count(),
            'first_few_lines' => $codelines->take(3)->pluck('codeline')->toArray()
        ]);
        
        // Initialize state variables for this processing run
        $codeblocks = [];
        $currentBlock = null;
        $openerLevel = null;
        
        foreach ($codelines as $codeline) {
            // Skip page_setup tags
            if ($codeline->purpose_key === 'page_setup') {
                continue;
            }
            
            // Start a new codeblock ONLY if we're not already in one
            if ($codeline->is_opener && $codeline->purpose_key !== 'page_setup' && $currentBlock === null) {
                // Start new block
                $currentBlock = [
                    'opener_level' => $codeline->level,
                    'lines' => [$codeline]
                ];
                $openerLevel = $codeline->level;
            }
            // Add lines to current block if we're inside one
            elseif ($currentBlock !== null) {
                $currentBlock['lines'][] = $codeline;
                
                // Check if we've returned to the opener's level (block complete)
                if ($codeline->level <= $openerLevel && $codeline !== $currentBlock['lines'][0]) {
                    // Block is complete, finalize it
                    $codeblocks[] = $this->finalizeBlock($currentBlock);
                    $currentBlock = null;
                    $openerLevel = null;
                    
                    // Check if this line that completed the block is also an opener for a new block
                    if ($codeline->is_opener && $codeline->purpose_key !== 'page_setup') {
                        // Start new block with this line
                        $currentBlock = [
                            'opener_level' => $codeline->level,
                            'lines' => [$codeline]
                        ];
                        $openerLevel = $codeline->level;
                    }
                }
            }
        }
        
        // Handle any remaining open block
        if ($currentBlock !== null) {
            $codeblocks[] = $this->finalizeBlock($currentBlock);
        }
        
        // Save codeblocks to database
        $savedCount = 0;
        DB::transaction(function () use ($codeblocks, &$savedCount) {
            foreach ($codeblocks as $blockData) {
                // Create codeblock with JSON data
                $codeblock = Codeblock::create([
                    'codeblock' => $blockData['json_data'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Get the parent codeline (first line in the block - the opener)
                $parentCodelineId = $blockData['lines'][0]->master_codeline_id ?? null;
                
                // Create pivot entries linking each codeline to this codeblock
                foreach ($blockData['lines'] as $line) {
                    if ($line->master_codeline_id) {
                        DB::table('codeline_codeblock')->insert([
                            'codeline_id' => $line->master_codeline_id,
                            'codeblock_id' => $codeblock->id,
                            'parent_codeline_id' => $parentCodelineId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
                
                $savedCount++;
                
                Log::info('Created codeblock with links', [
                    'codeblock_id' => $codeblock->id,
                    'lines_count' => count($blockData['lines']),
                    'pivot_entries_created' => count($blockData['lines']),
                    'parent_codeline_id' => $parentCodelineId,
                    'opener_level' => $blockData['opener_level'],
                    'first_line' => $blockData['lines'][0]->codeline ?? '',
                    'last_line' => end($blockData['lines'])->codeline ?? ''
                ]);
            }
        });
        
        return [
            'codeblocks_created' => $savedCount,
            'total_lines_processed' => $codelines->count()
        ];
    }
    
    protected function finalizeBlock(array $blockData): array
    {
        // Combine all codelines into a single block string
        $combinedCodelines = '';
        
        foreach ($blockData['lines'] as $line) {
            $combinedCodelines .= trim($line->codeline) . ' ';
        }
        
        // Create simple JSON with just the combined content
        $jsonData = trim($combinedCodelines);
        
        return [
            'opener_level' => $blockData['opener_level'],
            'lines' => $blockData['lines'],
            'json_data' => $jsonData
        ];
    }
}