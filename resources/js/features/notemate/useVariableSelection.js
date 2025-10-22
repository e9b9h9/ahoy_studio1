import { ref, onMounted, onUnmounted } from 'vue';

export function useVariableSelection() {
    const selectedText = ref('');
    const isSelecting = ref(false);
    const selectedCodeline = ref(null);
    const loading = ref(false);
    const error = ref(null);

    // Add variable to database
    const addVariableToDatabase = async (variableName, codelineId = null) => {
        loading.value = true;
        error.value = null;

        try {
            // Get CSRF token from meta tag or form
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Fallback: try to get from any hidden CSRF input
            if (!csrfToken) {
                csrfToken = document.querySelector('input[name="_token"]')?.value;
            }
            
            console.log('Making request to add variable:', variableName);
            console.log('CSRF token found:', csrfToken ? 'Yes' : 'No');

            const response = await fetch('/api/notemate/variables', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    variable: variableName,
                    type: 'user_added',
                    codeline_id: codelineId
                })
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response received:', text.substring(0, 200));
                throw new Error(`Server returned HTML instead of JSON. Status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Response data:', data);

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}: Failed to add variable`);
            }

            return data;
        } catch (err) {
            error.value = err.message;
            console.error('Error adding variable:', err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // Handle text selection
    const handleSelection = () => {
        const selection = window.getSelection();
        const text = selection.toString().trim();
        
        if (text && text.length > 0) {
            selectedText.value = text;
            isSelecting.value = true;
            
            // Try to find which codeline this selection belongs to
            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            
            // Walk up the DOM to find the codeline container
            let element = container.nodeType === Node.TEXT_NODE ? container.parentElement : container;
            while (element && !element.dataset.codelineId) {
                element = element.parentElement;
            }
            
            if (element && element.dataset.codelineId) {
                selectedCodeline.value = {
                    id: parseInt(element.dataset.codelineId),
                    element: element
                };
            }
        } else {
            selectedText.value = '';
            isSelecting.value = false;
            selectedCodeline.value = null;
        }
    };

    // Handle keyboard shortcuts
    const handleKeyDown = async (event) => {
        // Alt + A to add selected text as variable
        if (event.altKey && event.key.toLowerCase() === 'a' && selectedText.value) {
            event.preventDefault();
            
            const variableName = selectedText.value;
            
            // Validate variable name (basic check)
            if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(variableName)) {
                alert(`"${variableName}" is not a valid variable name. Variable names should start with a letter or underscore and contain only letters, numbers, and underscores.`);
                return;
            }

            try {
                const result = await addVariableToDatabase(
                    variableName, 
                    selectedCodeline.value?.id
                );
                
                console.log('✅ Variable added successfully:', result);
                
                // Show success feedback
                if (selectedCodeline.value?.element) {
                    // Add temporary success visual feedback
                    const element = selectedCodeline.value.element;
                    element.style.backgroundColor = '#10b981';
                    element.style.transition = 'background-color 0.3s';
                    
                    setTimeout(() => {
                        element.style.backgroundColor = '';
                    }, 1000);
                }
                
                // Clear selection
                window.getSelection().removeAllRanges();
                selectedText.value = '';
                isSelecting.value = false;
                selectedCodeline.value = null;
                
                // Notify parent components that variables have changed
                window.dispatchEvent(new CustomEvent('variable-added', { 
                    detail: { variable: result.variable } 
                }));
                
            } catch (err) {
                if (err.message.includes('already exists')) {
                    alert(`Variable "${variableName}" already exists in the database.`);
                } else {
                    alert(`Failed to add variable: ${err.message}`);
                }
            }
        }
    };

    // Set up event listeners
    onMounted(() => {
        document.addEventListener('selectionchange', handleSelection);
        document.addEventListener('keydown', handleKeyDown);
    });

    // Clean up event listeners
    onUnmounted(() => {
        document.removeEventListener('selectionchange', handleSelection);
        document.removeEventListener('keydown', handleKeyDown);
    });

    return {
        selectedText,
        isSelecting,
        selectedCodeline,
        loading,
        error,
        addVariableToDatabase
    };
}