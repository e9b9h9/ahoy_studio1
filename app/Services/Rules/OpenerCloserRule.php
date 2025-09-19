<?php

namespace App\Services\Rules;

use App\Contracts\CodelineRuleInterface;

class OpenerCloserRule implements CodelineRuleInterface
{
    public function apply(array $codelineData, array $context): array
    {
        $lineContent = $codelineData['codeline'] ?? '';
        $trimmed = trim($lineContent);
        
        $isOpener = false;
        $isCloser = false;
        
        // Check for HTML/XML opening tags (not self-closing)
        // Matches <tag> or <tag attributes> but not </tag> or <tag/>
        if (preg_match('/<(?!\/)[^>\/]+(?<!\/)>/i', $trimmed)) {
            // Make sure it's not a closing tag or self-closing tag
            if (!str_starts_with($trimmed, '</') && !str_ends_with(rtrim($trimmed, '>'), '/')) {
                $isOpener = true;
            }
        }
        
        // Check for HTML/XML closing tags
        if (preg_match('/<\/[^>]+>/i', $trimmed)) {
            $isCloser = true;
        }
        
        // Check if line ends with opening bracket or parenthesis
        $trimmedEnd = rtrim($trimmed);
        if (strlen($trimmedEnd) > 0) {
            $lastChar = substr($trimmedEnd, -1);
            if ($lastChar === '{' || $lastChar === '(') {
                $isOpener = true;
            }
        }
        
        // Check if line starts with or contains standalone closing bracket or parenthesis
        if (str_starts_with($trimmed, '}') || str_starts_with($trimmed, ')')) {
            $isCloser = true;
        }
        
        // Also check if line ends with closing bracket or parenthesis (for inline closers)
        if (strlen($trimmedEnd) > 0) {
            $lastChar = substr($trimmedEnd, -1);
            if ($lastChar === '}' || $lastChar === ')') {
                // Check if it's not part of an opener (like function() {})
                if (!str_contains($trimmed, '{') || strrpos($trimmed, '}') > strrpos($trimmed, '{')) {
                    $isCloser = true;
                }
            }
        }
        
        // Check for self-closing tags (these are both openers and closers)
        if (preg_match('/<[^>]+\/>/i', $trimmed)) {
            $isOpener = true;
            $isCloser = true;
        }
        
        // Set the values in the codeline data
        if ($isOpener) {
            $codelineData['is_opener'] = true;
        }
        
        if ($isCloser) {
            $codelineData['is_closer'] = true;
        }
        
        return $codelineData;
    }
}