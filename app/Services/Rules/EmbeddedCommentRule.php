<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class EmbeddedCommentRule implements CodelineRuleInterface
{
    public function apply(array $codelineData, array $context): array
    {
        $lineContent = $codelineData['codeline'] ?? '';
        
        // Check for inline comments
        $processed = $this->extractInlineComment($lineContent);
        
        if ($processed['hasComment']) {
            // Update the codeline to remove the comment
            $codelineData['codeline'] = $processed['code'];
            
            // Add or combine the comment
            if ($codelineData['comment'] !== null) {
                // Combine with existing comment
                $codelineData['comment'] = $codelineData['comment'] . ' ' . $processed['comment'];
            } else {
                // Set the comment
                $codelineData['comment'] = $processed['comment'];
            }
        }
        
        return $codelineData;
    }
    
    protected function extractInlineComment(string $line): array
    {
        // Check for // comments (most common)
        if (preg_match('/^(.*?)\/\/(.*)$/', $line, $matches)) {
            // Make sure it's not inside a string
            if (!$this->isInsideString($matches[1], '//')) {
                return [
                    'hasComment' => true,
                    'code' => rtrim($matches[1]),
                    'comment' => trim($matches[2])
                ];
            }
        }
        
        // Check for # comments (Python, Shell)
        if (preg_match('/^(.*?)#(.*)$/', $line, $matches)) {
            // Make sure it's not inside a string
            if (!$this->isInsideString($matches[1], '#')) {
                return [
                    'hasComment' => true,
                    'code' => rtrim($matches[1]),
                    'comment' => trim($matches[2])
                ];
            }
        }
        
        // Check for /* */ comments on same line
        if (preg_match('/^(.*?)\/\*(.*?)\*\/(.*)$/', $line, $matches)) {
            // Code before and after the comment
            $code = trim($matches[1] . ' ' . $matches[3]);
            return [
                'hasComment' => true,
                'code' => $code,
                'comment' => trim($matches[2])
            ];
        }
        
        // Check for <!-- --> comments on same line
        if (preg_match('/^(.*?)<!--(.*?)-->(.*)$/', $line, $matches)) {
            // Code before and after the comment
            $code = trim($matches[1] . ' ' . $matches[3]);
            return [
                'hasComment' => true,
                'code' => $code,
                'comment' => trim($matches[2])
            ];
        }
        
        // Check for -- SQL comments
        if (preg_match('/^(.*?)--(.*)$/', $line, $matches)) {
            // Make sure it's not inside a string
            if (!$this->isInsideString($matches[1], '--')) {
                return [
                    'hasComment' => true,
                    'code' => rtrim($matches[1]),
                    'comment' => trim($matches[2])
                ];
            }
        }
        
        return [
            'hasComment' => false,
            'code' => $line,
            'comment' => null
        ];
    }
    
    protected function isInsideString(string $code, string $commentMarker): bool
    {
        // Simple check for quotes before the comment marker
        // Count unescaped quotes
        $singleQuotes = substr_count($code, "'") - substr_count($code, "\\'");
        $doubleQuotes = substr_count($code, '"') - substr_count($code, '\\"');
        
        // If odd number of quotes, we're likely inside a string
        return ($singleQuotes % 2 !== 0) || ($doubleQuotes % 2 !== 0);
    }
}