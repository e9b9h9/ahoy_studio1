<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class MultilineCommentRule implements CodelineRuleInterface
{
    protected bool $inHtmlComment = false;
    protected bool $inBlockComment = false;
    protected array $commentBuffer = [];
    protected int $commentStartIndex = -1;

    public function apply(array $codelineData, array $context): array
    {
        $lineContent = trim($codelineData['codeline'] ?? '');
        $lineNumber = $context['line_number'] ?? 0;
        
        // Check for HTML comment start
        if (str_contains($lineContent, '<!--') && !str_contains($lineContent, '-->')) {
            $this->startComment($lineNumber, $codelineData, 'html');
            return ['skip' => true]; // Skip the first line - it's added to buffer
        }
        
        // Check for block comment start
        if (str_contains($lineContent, '/*') && !str_contains($lineContent, '*/')) {
            $this->startComment($lineNumber, $codelineData, 'block');
            return ['skip' => true]; // Skip the first line - it's added to buffer
        }
        
        // Check for HTML comment end
        if ($this->inHtmlComment && str_contains($lineContent, '-->')) {
            return $this->endComment($lineNumber, $codelineData, '-->');
        }
        
        // Check for block comment end
        if ($this->inBlockComment && str_contains($lineContent, '*/')) {
            return $this->endComment($lineNumber, $codelineData, '*/');
        }
        
        // If we're inside a comment, add to buffer
        if ($this->inHtmlComment || $this->inBlockComment) {
            $this->commentBuffer[] = $codelineData;
            // Return empty data - this line will be combined
            return ['skip' => true];
        }
        
        return $codelineData;
    }
    
    protected function startComment(int $lineNumber, array $codelineData, string $type): void
    {
        if ($type === 'html') {
            $this->inHtmlComment = true;
        } else {
            $this->inBlockComment = true;
        }
        
        $this->commentStartIndex = $lineNumber;
        $this->commentBuffer = [$codelineData];
    }
    
    protected function endComment(int $lineNumber, array $codelineData, string $endMarker): array
    {
        // Add closing line to buffer
        $this->commentBuffer[] = $codelineData;
        
        // Combine all lines in buffer
        $combinedContent = '';
        $firstLine = $this->commentBuffer[0] ?? [];
        
        foreach ($this->commentBuffer as $bufferedLine) {
            $combinedContent .= trim($bufferedLine['codeline']) . ' ';
        }
        
        // Create combined codeline, preserving data from the first line
        $combinedCodeline = [
            'codeline' => trim($combinedContent),
            'comment' => $firstLine['comment'] ?? null,
            'variables' => $firstLine['variables'] ?? null,
            'purpose_key' => $firstLine['purpose_key'] ?? null,
            'file_location' => $firstLine['file_location'] ?? null,
            'is_opener' => $firstLine['is_opener'] ?? null,
            'is_closer' => $firstLine['is_closer'] ?? null,
            'language_id' => $firstLine['language_id'] ?? $codelineData['language_id'] ?? null,
            'master_codeline_id' => $firstLine['master_codeline_id'] ?? null,
            'created_at' => $firstLine['created_at'] ?? now(),
            'updated_at' => now()
        ];
        
        // Reset state
        $this->inHtmlComment = false;
        $this->inBlockComment = false;
        $this->commentBuffer = [];
        $this->commentStartIndex = -1;
        
        return $combinedCodeline;
    }
}