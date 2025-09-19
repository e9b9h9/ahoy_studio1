<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class ConsecutiveCommentRule implements CodelineRuleInterface
{
    protected array $commentBuffer = [];
    protected bool $inCommentBlock = false;

    public function apply(array $codelineData, array $context): array
    {
        $lineContent = trim($codelineData['codeline'] ?? '');
        
        // Check if this line is a comment
        if ($this->isComment($lineContent)) {
            // Add to buffer
            $this->commentBuffer[] = $this->extractCommentText($lineContent);
            $this->inCommentBlock = true;
            
            // Skip this line for now
            return ['skip' => true];
        }
        
        // If we were in a comment block and hit a non-comment line
        if ($this->inCommentBlock && !$this->isComment($lineContent)) {
            // Combine all buffered comments
            if (!empty($this->commentBuffer)) {
                $combinedComment = implode(' ', $this->commentBuffer);
                
                // Create a combined comment line to be processed by CommentRule
                $commentLine = [
                    'codeline' => '// ' . $combinedComment,
                    'comment' => null,
                    'language_id' => $codelineData['language_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // Reset buffer
                $this->commentBuffer = [];
                $this->inCommentBlock = false;
                
                // Return both the combined comment and current line
                // We need to modify the service to handle returning multiple lines
                return [
                    'multiple' => [
                        $commentLine,
                        $codelineData
                    ]
                ];
            }
        }
        
        return $codelineData;
    }
    
    protected function isComment(string $line): bool
    {
        $trimmed = trim($line);
        
        return str_starts_with($trimmed, '<!--') ||
               str_starts_with($trimmed, '//') ||
               str_starts_with($trimmed, '/*') ||
               str_starts_with($trimmed, '#') ||
               str_starts_with($trimmed, '--') ||
               str_starts_with($trimmed, '"""') ||
               str_starts_with($trimmed, "'''");
    }
    
    protected function extractCommentText(string $line): string
    {
        $trimmed = trim($line);
        
        // HTML comments
        if (str_starts_with($trimmed, '<!--')) {
            $comment = preg_replace('/^<!--\s*/', '', $trimmed);
            $comment = preg_replace('/\s*-->$/', '', $comment);
            return trim($comment);
        }
        
        // Single line // comments
        if (str_starts_with($trimmed, '//')) {
            return trim(substr($trimmed, 2));
        }
        
        // Block comments /* */
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
        
        // Python docstrings
        if (str_starts_with($trimmed, '"""') || str_starts_with($trimmed, "'''")) {
            $comment = substr($trimmed, 3);
            if (str_ends_with($comment, '"""') || str_ends_with($comment, "'''")) {
                $comment = substr($comment, 0, -3);
            }
            return trim($comment);
        }
        
        return $trimmed;
    }
}