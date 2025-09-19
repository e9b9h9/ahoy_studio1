import { useShopbuddyStore } from '../shopbuddy.store.js';
import axios from 'axios';

/**
 * Add snippet feature to notemate_snippetfeatures table
 * 
 * Command: sb notemate [feature text]
 * 
 * @param {string[]} args - Command arguments, all args joined as feature text
 * @param {string} pageId - Page identifier for output
 * @returns {void}
 */
export const addSnippetFeature = async (args = [], pageId = 'notemate') => {
    const store = useShopbuddyStore();
    const featureText = args.join(' ').trim();
    
    if (!featureText || featureText === '') {
        store.addOutput('Please provide feature text. Usage: notemate [feature description]', pageId);
        return;
    }
    
    store.addOutput(`Adding snippet feature: ${featureText}`, pageId);
    
    // Save snippet feature to database
    try {
        await axios.post('/notemate/snippetfeatures', {
            snippetfeature: featureText
        });
        store.addOutput('Snippet feature added successfully', pageId);
    } catch (error) {
        store.addOutput('Error adding snippet feature to database', pageId);
        if (error.response?.data?.errors?.snippetfeature) {
            store.addOutput(`Error: ${error.response.data.errors.snippetfeature[0]}`, pageId);
        }
    }
};