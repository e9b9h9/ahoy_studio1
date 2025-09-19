<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class CodelineLevelRule implements CodelineRuleInterface
{
    protected int $currentLevel = 0;
    protected array $processedLines = [];
    
    public function apply(array $codelineData, array $context): array
    {
        $purposeKey = $codelineData['purpose_key'] ?? '';
        $isOpener = $codelineData['is_opener'] ?? false;
        $isCloser = $codelineData['is_closer'] ?? false;
        
        // Page setup tags always get level 0
        if ($purposeKey === 'page_setup') {
            $codelineData['level'] = 0;
            $this->currentLevel = 0;
            return $codelineData;
        }
        
        // Handle closers first - they get reduced level on their own line
        if (!$isOpener && $isCloser) {
            // Only closer - decrease level for this line AND next line
            // Example: });
            $this->currentLevel = max(0, $this->currentLevel - 1);
            $codelineData['level'] = $this->currentLevel;
        } else {
            // Set current line's level (for non-closers)
            $codelineData['level'] = $this->currentLevel;
            
            // Determine level change for next line
            if ($isOpener && $isCloser) {
                // Both opener and closer - no level change
                // Example: <Component />
                // Current level stays the same
            } elseif ($isOpener && !$isCloser) {
                // Only opener - increase level for next line
                // Example: const props = defineProps({
                $this->currentLevel++;
            }
        }
        // If neither opener nor closer, inherit current level (no change)
        
        return $codelineData;
    }
}