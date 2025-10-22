import { ref } from 'vue';

// Color palette for variables
const variableColors = [
    'bg-blue-100 text-blue-800 border-blue-200',
    'bg-green-100 text-green-800 border-green-200', 
    'bg-purple-100 text-purple-800 border-purple-200',
    'bg-red-100 text-red-800 border-red-200',
    'bg-yellow-100 text-yellow-800 border-yellow-200',
    'bg-indigo-100 text-indigo-800 border-indigo-200',
    'bg-pink-100 text-pink-800 border-pink-200',
    'bg-teal-100 text-teal-800 border-teal-200',
    'bg-orange-100 text-orange-800 border-orange-200',
    'bg-cyan-100 text-cyan-800 border-cyan-200'
];

export function useVariableHighlighting() {
    // Track variable colors (variable name -> color class)
    const variableColorMap = ref(new Map());
    const variables = ref([]);
    const tempCodelineVariables = ref(new Set()); // Variables found in temp_codelines but not in database
    const loading = ref(false);
    const error = ref(null);
    
    // Dark red color for missing variables
    const missingVariableColor = 'bg-red-900 text-red-100 border-red-800';

    // Fetch variables from the database
    const fetchVariables = async () => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await fetch('/api/notemate/variables-codelines');
            if (!response.ok) {
                throw new Error('Failed to fetch variables');
            }
            const data = await response.json();
            variables.value = data.variables || {};
            
            // Assign colors to all variables
            Object.keys(variables.value).forEach(variableName => {
                getVariableColor(variableName);
            });
            
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching variables:', err);
        } finally {
            loading.value = false;
        }
    };

    // Get or assign color for a variable
    const getVariableColor = (variableName) => {
        // If it's a missing variable (found in temp_codelines but not in database), return dark red
        if (isMissingVariable(variableName)) {
            return missingVariableColor;
        }
        
        // Otherwise, use normal color assignment for official variables
        if (!variableColorMap.value.has(variableName)) {
            const colorIndex = variableColorMap.value.size % variableColors.length;
            variableColorMap.value.set(variableName, variableColors[colorIndex]);
        }
        return variableColorMap.value.get(variableName);
    };

    // Collect variables from temp_codelines.variables field
    const collectTempCodelineVariables = (codelines) => {
        const foundVariables = new Set();
        
        codelines.forEach(codeline => {
            if (codeline.variables) {
                try {
                    // Handle both string and JSON formats
                    let variablesList = [];
                    if (typeof codeline.variables === 'string') {
                        // Try to parse as JSON first
                        try {
                            variablesList = JSON.parse(codeline.variables);
                        } catch {
                            // If not JSON, split by common separators
                            variablesList = codeline.variables.split(/[,;|\s]+/)
                                .map(v => v.trim())
                                .filter(v => v && /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(v));
                        }
                    } else if (Array.isArray(codeline.variables)) {
                        variablesList = codeline.variables;
                    }
                    
                    // Add variables that aren't in the official database
                    variablesList.forEach(variable => {
                        if (typeof variable === 'string' && !variableColorMap.value.has(variable)) {
                            foundVariables.add(variable);
                        }
                    });
                } catch (err) {
                    console.warn('Error parsing variables for codeline:', codeline.id, err);
                }
            }
        });
        
        tempCodelineVariables.value = foundVariables;
    };

    // Check if a word is a tracked variable (either official or found in temp_codelines)
    const isTrackedVariable = (word) => {
        return variableColorMap.value.has(word) || tempCodelineVariables.value.has(word);
    };
    
    // Check if a word is a missing variable (found in temp_codelines but not in database)
    const isMissingVariable = (word) => {
        return tempCodelineVariables.value.has(word) && !variableColorMap.value.has(word);
    };

    // Get variable details (type and metadata)
    const getVariableDetails = (variableName) => {
        return variables.value[variableName] || null;
    };

    // Parse code into clickable words and non-clickable parts
    const parseCodeIntoWords = (codeText) => {
        if (!codeText) return [];
        
        // Enhanced regex to capture different types of identifiers:
        // - Quoted strings: capture quotes separately so we can highlight just the content
        // - File paths (./shopbuddy.store.js, @/components/ui/button)
        // - Object properties (this.property, object.method)
        // - Simple words (handleMessage)
        const quotedStringRegex = /(["'])([\w\/.@-]+)\1/g;
        const identifierRegex = /([\w@][\w\/.@-]*[\w]|[\w]+)/g;
        
        let lastIndex = 0;
        const parts = [];
        
        // First pass: handle quoted strings
        let quotedMatch;
        const quotedRanges = [];
        
        while ((quotedMatch = quotedStringRegex.exec(codeText)) !== null) {
            quotedRanges.push({
                start: quotedMatch.index,
                end: quotedMatch.index + quotedMatch[0].length,
                fullMatch: quotedMatch[0],
                quote: quotedMatch[1],
                content: quotedMatch[2],
                contentStart: quotedMatch.index + 1,
                contentEnd: quotedMatch.index + quotedMatch[0].length - 1
            });
        }
        
        // Second pass: process the entire string
        let currentIndex = 0;
        
        for (const quotedRange of quotedRanges) {
            // Add text before the quoted string
            if (quotedRange.start > currentIndex) {
                const beforeText = codeText.slice(currentIndex, quotedRange.start);
                parts.push(...parseUnquotedText(beforeText, parts.length, identifierRegex));
            }
            
            // Add opening quote
            parts.push({
                text: quotedRange.quote,
                isClickable: false,
                isVariable: false,
                variableColor: null,
                key: parts.length
            });
            
            // Add the content (potentially highlighted)
            const isVariable = isTrackedVariable(quotedRange.content);
            const isMissing = isMissingVariable(quotedRange.content);
            parts.push({
                text: quotedRange.content,
                isClickable: true,
                isVariable: isVariable,
                isMissingVariable: isMissing,
                variableColor: isVariable ? getVariableColor(quotedRange.content) : null,
                key: parts.length,
                cleanText: quotedRange.content
            });
            
            // Add closing quote
            parts.push({
                text: quotedRange.quote,
                isClickable: false,
                isVariable: false,
                variableColor: null,
                key: parts.length
            });
            
            currentIndex = quotedRange.end;
        }
        
        // Add remaining text after last quoted string
        if (currentIndex < codeText.length) {
            const remainingText = codeText.slice(currentIndex);
            parts.push(...parseUnquotedText(remainingText, parts.length, identifierRegex));
        }
        
        return parts;
    };
    
    // Helper function to parse unquoted text for identifiers
    const parseUnquotedText = (text, startingKey, identifierRegex) => {
        const parts = [];
        let lastIndex = 0;
        let match;
        
        // Reset regex
        identifierRegex.lastIndex = 0;
        
        while ((match = identifierRegex.exec(text)) !== null) {
            // Add any text before this match
            if (match.index > lastIndex) {
                parts.push({
                    text: text.slice(lastIndex, match.index),
                    isClickable: false,
                    isVariable: false,
                    variableColor: null,
                    key: startingKey + parts.length
                });
            }
            
            // Process the matched identifier
            const identifier = match[0];
            const isVariable = isTrackedVariable(identifier);
            const isMissing = isMissingVariable(identifier);
            
            parts.push({
                text: identifier,
                isClickable: true,
                isVariable: isVariable,
                isMissingVariable: isMissing,
                variableColor: isVariable ? getVariableColor(identifier) : null,
                key: startingKey + parts.length,
                cleanText: identifier
            });
            
            lastIndex = match.index + match[0].length;
        }
        
        // Add any remaining text
        if (lastIndex < text.length) {
            parts.push({
                text: text.slice(lastIndex),
                isClickable: false,
                isVariable: false,
                variableColor: null,
                key: startingKey + parts.length
            });
        }
        
        return parts;
    };

    return {
        variables,
        variableColorMap,
        tempCodelineVariables,
        loading,
        error,
        fetchVariables,
        collectTempCodelineVariables,
        getVariableColor,
        isTrackedVariable,
        isMissingVariable,
        getVariableDetails,
        parseCodeIntoWords
    };
}