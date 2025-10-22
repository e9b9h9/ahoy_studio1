<?php

namespace App\Http\Controllers;

use App\Models\TempCodeline;
use App\Models\MasterCodeline;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MasterCodelineController extends Controller
{
    public function create(Request $request)
    {
        // Get all temp_codelines that don't have a master_codeline_id yet
        $tempCodelines = TempCodeline::whereNull('master_codeline_id')->get();
        
        $masterCodelinesCreated = 0;
        $masterCodelinesLinked = 0;
        $variableLinksCreated = 0;
        
        foreach ($tempCodelines as $tempCodeline) {
            $codeline = $tempCodeline->codeline;
            
            // Skip empty or whitespace-only lines
            if (empty(trim($codeline))) {
                continue;
            }
            
            // Check if this exact codeline already exists in master_codelines
            $existingMaster = MasterCodeline::where('codeline', $codeline)->first();
            
            if ($existingMaster) {
                // Link to existing master codeline
                $tempCodeline->update(['master_codeline_id' => $existingMaster->id]);
                $masterCodelinesLinked++;
                
                // Create variable_codeline entries for existing master codeline
                $variableLinksCreated += $this->linkVariablesToCodeline($tempCodeline, $existingMaster->id);
            } else {
                // Create new master codeline record
                $masterCodeline = MasterCodeline::create([
                    'codeline' => $codeline,
                    'comment' => $tempCodeline->comment,
                    'variables' => $tempCodeline->variables,
                    'purpose_key' => $tempCodeline->purpose_key,
                    'file_location' => $tempCodeline->file_location,
                    'is_opener' => $tempCodeline->is_opener ?? false,
                    'is_closer' => $tempCodeline->is_closer ?? false,
                    'language_id' => $tempCodeline->language_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Link the temp codeline to the new master
                $tempCodeline->update(['master_codeline_id' => $masterCodeline->id]);
                $masterCodelinesCreated++;
                
                // Create variable_codeline entries for new master codeline
                $variableLinksCreated += $this->linkVariablesToCodeline($tempCodeline, $masterCodeline->id);
            }
        }
        
        Log::info('Master codeline creation completed', [
            'new_master_codelines' => $masterCodelinesCreated,
            'linked_to_existing' => $masterCodelinesLinked,
            'total_processed' => $masterCodelinesCreated + $masterCodelinesLinked,
            'variable_links_created' => $variableLinksCreated
        ]);
        
        return response()->json([
            'success' => true,
            'new_master_codelines' => $masterCodelinesCreated,
            'linked_to_existing' => $masterCodelinesLinked,
            'total_processed' => $masterCodelinesCreated + $masterCodelinesLinked,
            'variable_links_created' => $variableLinksCreated
        ]);
    }
    
    /**
     * Link variables to a master codeline through the variable_codeline pivot table
     * @return int Number of variable links created
     */
    protected function linkVariablesToCodeline($tempCodeline, $masterCodelineId)
    {
        $linksCreated = 0;
        
        if (!$tempCodeline->variables) {
            return $linksCreated;
        }
        
        $variableNames = json_decode($tempCodeline->variables, true);
        if (!is_array($variableNames)) {
            return $linksCreated;
        }
        
        foreach ($variableNames as $variableName) {
            // Find the variable record
            $variable = Variable::where('variable', $variableName)->first();
            if (!$variable) {
                continue;
            }
            
            // Check if this variable-codeline link already exists
            $exists = DB::table('variable_codeline')
                ->where('variable_id', $variable->id)
                ->where('codeline_id', $masterCodelineId)
                ->exists();
            
            if (!$exists) {
                // Create the link in variable_codeline table
                DB::table('variable_codeline')->insert([
                    'variable_id' => $variable->id,
                    'codeline_id' => $masterCodelineId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $linksCreated++;
                
                Log::debug('Created variable_codeline link', [
                    'variable' => $variableName,
                    'variable_id' => $variable->id,
                    'codeline_id' => $masterCodelineId
                ]);
            }
        }
        
        return $linksCreated;
    }
}