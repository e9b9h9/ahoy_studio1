<?php

namespace App\Services;

use App\Contracts\CodelineRuleInterface;

class CodelineProcessingService
{
    protected array $rules = [];

    public function addRule(CodelineRuleInterface $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function processLines(array $lines, array $context): array
    {
        $processedLines = [];

        foreach ($lines as $lineNumber => $lineContent) {
            $codelineData = [
                'codeline' => $lineContent,
                'comment' => null,
                'language_id' => $context['language_id'] ?? null,
                'level' => 0, // Will be set by CodelineLevelRule
                'line_number' => $lineNumber + 1, // Convert 0-based to 1-based
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Apply all rules to this line
            foreach ($this->rules as $rule) {
                $codelineData = $rule->apply($codelineData, array_merge($context, [
                    'line_number' => $lineNumber,
                    'line_content' => $lineContent
                ]));
                
                // If rule marked this line to skip, don't add to processed lines
                if (isset($codelineData['skip']) && $codelineData['skip']) {
                    continue 2; // Skip to next line
                }
                
                // If rule returned multiple lines
                if (isset($codelineData['multiple']) && is_array($codelineData['multiple'])) {
                    foreach ($codelineData['multiple'] as $line) {
                        $processedLines[] = $line;
                    }
                    continue 2; // Skip to next line
                }
            }

            $processedLines[] = $codelineData;
        }

        // Post-process to move comments to next line
        $finalLines = [];
        $pendingComment = null;
        
        foreach ($processedLines as $line) {
            // If we have a pending comment, add it to this line
            if ($pendingComment !== null && !$this->isComment($line['codeline'])) {
                $line['comment'] = $pendingComment;
                $pendingComment = null;
            }
            
            // Check if current line is a comment
            if ($this->isComment($line['codeline'])) {
                $pendingComment = $this->extractCommentText($line['codeline']);
                continue; // Skip adding this line
            }
            
            $finalLines[] = $line;
        }
        
        return $finalLines;
    }
    
    protected function isComment(string $line): bool
    {
        $trimmed = trim($line);
        
        return str_starts_with($trimmed, '<!--') ||
               str_starts_with($trimmed, '//') ||
               str_starts_with($trimmed, '/*') ||
               str_starts_with($trimmed, '#') ||
               str_starts_with($trimmed, '--');
    }
    
    protected function extractCommentText(string $line): string
    {
        $trimmed = trim($line);
        
        // Single line // comments
        if (str_starts_with($trimmed, '//')) {
            return trim(substr($trimmed, 2));
        }
        
        // HTML comments
        if (str_starts_with($trimmed, '<!--')) {
            $comment = preg_replace('/^<!--\s*/', '', $trimmed);
            $comment = preg_replace('/\s*-->$/', '', $comment);
            return trim($comment);
        }
        
        // Block comments
        if (str_starts_with($trimmed, '/*')) {
            $comment = preg_replace('/^\/\*\s*/', '', $trimmed);
            $comment = preg_replace('/\s*\*\/$/', '', $comment);
            return trim($comment);
        }
        
        // Python/Shell comments
        if (str_starts_with($trimmed, '#')) {
            return trim(substr($trimmed, 1));
        }
        
        // SQL comments
        if (str_starts_with($trimmed, '--')) {
            return trim(substr($trimmed, 2));
        }
        
        return $trimmed;
    }
}