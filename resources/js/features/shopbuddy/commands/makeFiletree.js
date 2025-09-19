import { useShopbuddyStore } from '../shopbuddy.store.js';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * Generate file tree for a specified path
 * 
 * Command: sb make filetree [path]
 * 
 * @param {string[]} args - Command arguments, expects path as first argument
 * @returns {void}
 */
export const makeFiletree = async (args = [], pageId = 'default') => {
    const store = useShopbuddyStore();
    
    // Handle quoted paths and regular paths
    let path;
    if (args.length === 0) {
        path = '';
    } else {
        const fullInput = args.join(' ');
        
        // Check if path is wrapped in quotes
        if ((fullInput.startsWith('"') && fullInput.endsWith('"')) || 
            (fullInput.startsWith("'") && fullInput.endsWith("'"))) {
            // Remove quotes
            path = fullInput.slice(1, -1);
        } else {
            // Join all args for paths with spaces
            path = fullInput;
        }
    }
      
    // If no path provided, set prefix and prompt for path
    if (!path || path.trim() === '') {
        // Determine the full command path based on current context
        const currentPrefix = store.getInitialPrefix(pageId);
        const fullCommandPath = currentPrefix ? `${currentPrefix} make filetree` : 'make filetree';
        store.setPrefix(pageId, fullCommandPath);
        store.addOutput('Enter folder path (use quotes for paths with spaces):', pageId);
        store.addOutput('Example: "C:\\Users\\emmah\\Herd\\project"', pageId);
        return;
    }
    
    // Reset prefix back to default after getting path
    store.setPrefix(pageId, '');
    store.addOutput(`Making file tree for: ${path}`, pageId);
    
    // Make the file tree using the provided path
    router.post('/make/filetree', {
        path: path
    }, {
        onSuccess: () => {
            store.addOutput('File tree created successfully', pageId);
        },
        onError: () => {
            store.addOutput('Error creating file tree', pageId);
        }
    });
};

/**
 * Generate file tree for a specified folder from existing codefolders
 * 
 * Command: sb make filetree from-codefolder [selection]
 * 
 * @param {string[]} args - Command arguments, expects selection as first argument
 * @returns {void}
 */
export const makeFiletreeFromCodefolder = async (args = [], pageId = 'default') => {
    const store = useShopbuddyStore();
    const selection = args[0]; // Get selection from args
      
    // If no selection provided, fetch and display list
    if (!selection || selection.trim() === '') {
        try {
            const response = await axios.get('/notemate/codefolders');
            const codefolders = response.data;
            
            if (codefolders.length === 0) {
                store.addOutput('No existing folders found. Use "make filetree [path]" to add one.', pageId);
                return;
            }
            
            // Store codefolders for later selection
            store.tempCodefolders = codefolders;
            
            store.addOutput('Select a folder to make file tree for:', pageId);
            store.addOutput('', pageId);
            codefolders.forEach((folder, index) => {
                store.addOutput(`${index + 1}. ${folder.path}`, pageId);
            });
            store.addOutput('', pageId);
            store.setPrefix(pageId, 'make filetree from-codefolder');
            store.addOutput('Enter folder number:', pageId);
            
        } catch (error) {
            store.addOutput('Error fetching folders from database', pageId);
        }
        return;
    }
    
    // Handle folder selection by number
    const folderNumber = parseInt(selection);
    const folders = store.tempCodefolders || [];
    
    if (isNaN(folderNumber) || folderNumber < 1 || folderNumber > folders.length) {
        store.addOutput('Invalid selection. Please enter a valid folder number.', pageId);
        return;
    }
    
    const selectedFolder = folders[folderNumber - 1];
    store.setPrefix(pageId, ''); // Reset to normal prefix (empty for root)
    store.addOutput(`Making file tree for: ${selectedFolder.path}`, pageId);
    
    // Make the actual file tree using the selected folder path
    router.post('/make/filetree', {
        path: selectedFolder.path
    }, {
        onSuccess: () => {
            store.addOutput('File tree created successfully', pageId);
        },
        onError: () => {
            store.addOutput('Error creating file tree', pageId);
        }
    });
    
    // Clear temporary storage
    store.tempCodefolders = null;
};

/**
 * Generate file tree for the currently working codefolder (is_working = true)
 * 
 * Command: sb make filetree is_working
 * 
 * @param {string[]} args - Command arguments (not used)
 * @returns {void}
 */
export const makeFiletreeIsWorking = async (args = [], pageId = 'default') => {
    const store = useShopbuddyStore();
    
    try {
        const response = await axios.get('/notemate/codefolders');
        const codefolders = response.data;
        
        // Find the working folder
        const workingFolder = codefolders.find(folder => folder.is_working === 1 || folder.is_working === true);
        
        if (!workingFolder) {
            store.addOutput('No working folder found. Use "file-watcher start" to set a working folder first.', pageId);
            return;
        }
        
        store.addOutput(`Making file tree for working folder: ${workingFolder.path}`, pageId);
        
        // Make the file tree using the working folder path
        router.post('/make/filetree', {
            path: workingFolder.path
        }, {
            onSuccess: (page) => {
                const response = page.props.flash || {};
                store.addOutput('File tree created successfully from working folder', pageId);
                if (response.items_created) {
                    store.addOutput(`Created ${response.items_created} items`, pageId);
                }
            },
            onError: (errors) => {
                store.addOutput('Error creating file tree from working folder', pageId);
                if (errors.message) {
                    store.addOutput(`Error: ${errors.message}`, pageId);
                }
            }
        });
        
    } catch (error) {
        store.addOutput('Error fetching working folder from database', pageId);
    }
};