<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class CommentRule implements CodelineRuleInterface
{
    protected ?string $pendingComment = null;

    public function apply(array $codelineData, array $context): array
    {
        $lineContent = trim($codelineData['codeline'] ?? '');
        
        // If we have a pending comment from previous line, add it to this line
        if ($this->pendingComment !== null) {
            $codelineData['comment'] = $this->pendingComment;
            $this->pendingComment = null;
        }
        
        // Check if current line is a comment (including multiline comments)
        if ($this->isComment($lineContent)) {
            // Extract the comment text
            $commentText = $this->extractCommentText($lineContent);
            
            // Store comment for next line
            $this->pendingComment = $commentText;
            
            // Skip this line as it's just a comment
            return ['skip' => true];
        }
        
        return $codelineData;
    }
    
    protected function isComment(string $line): bool
    {
        $trimmed = trim($line);
        
        // Check for different comment patterns
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