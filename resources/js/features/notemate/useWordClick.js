export function useWordClick() {
    // Handle word click functionality
    const handleWordClick = (part, codeline, isTrackedVariable, getVariableColor, isMissingVariable) => {
        const displayText = part.text || part;
        const cleanText = part.cleanText || part.text || part;
        
        console.log('Clicked word:', displayText, 'in codeline:', codeline.line_number);
        
        if (isTrackedVariable(cleanText)) {
            if (part.isMissingVariable || (isMissingVariable && isMissingVariable(cleanText))) {
                console.log('🔴 MISSING VARIABLE - Found in temp_codelines but not in database:', cleanText);
                console.log('This variable should be added to the official variables database');
            } else {
                console.log('✅ TRACKED VARIABLE:', cleanText, 'with color:', getVariableColor(cleanText));
            }
            console.log('Clean identifier:', cleanText);
        }
        
        // TODO: Add more functionality here
        // Examples of what could be added:
        // - Navigate to variable definition
        // - Show variable usage across files
        // - Open variable details in sidebar
        // - Add to watch list
        // - Rename variable
        // - Auto-add missing variables to database
    };

    // Future: Add more word interaction functions
    const handleWordHover = (word, codeline) => {
        // TODO: Show tooltip with word information
    };

    const handleWordDoubleClick = (word, codeline) => {
        // TODO: Select all instances of this word
    };

    return {
        handleWordClick,
        handleWordHover,
        handleWordDoubleClick
    };
}