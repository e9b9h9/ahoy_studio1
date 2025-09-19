import { useShopbuddyStore } from '../shopbuddy.store.js';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * Start file watcher with existing folder selection
 * 
 * Command: sb notemate file-watcher start [folder-number]
 * 
 * @param {string[]} args - Command arguments, expects folder number as first argument
 * @returns {void}
 */
export const startFileWatcher = async (args = [], pageId = 'notemate') => {
    const store = useShopbuddyStore();
    const selection = args[0];
    
    // If no selection provided, fetch and display list
    if (!selection || selection.trim() === '') {
        try {
            const response = await axios.get('/notemate/codefolders');
            const codefolders = response.data;
            
            if (codefolders.length === 0) {
                store.addOutput('No existing folders found. Use "file-watcher start new [path]" to add one.', pageId);
                return;
            }
            
            store.addOutput('Select a folder to start monitoring:', pageId);
            store.addOutput('', pageId);
            codefolders.forEach((folder, index) => {
                store.addOutput(`${index + 1}. ${folder.path}`, pageId);
            });
            store.addOutput('', pageId);
            store.setPrefix(pageId, 'notemate file-watcher start');
            store.addOutput('Enter folder number:', pageId);
            
            // Store folders for later selection
            store.tempCodefolders = codefolders;
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
    store.setPrefix(pageId, 'notemate');
    store.addOutput(`Starting file watcher for: ${selectedFolder.path}`, pageId);
    
    // Update is_working status in database
    try {
        await axios.put(`/notemate/codefolders/${selectedFolder.id}/set-working`);
        store.addOutput('File watcher is now monitoring changes', pageId);
    } catch (error) {
        store.addOutput('Error updating folder status', pageId);
    }
    
    // Clear temporary storage
    store.tempCodefolders = null;
};

/**
 * Start file watcher with new folder path
 * 
 * Command: sb notemate file-watcher start new [folder-path]
 * 
 * @param {string[]} args - Command arguments, expects folder path as first argument
 * @returns {void}
 */
export const startFileWatcherNew = (args = [], pageId = 'notemate') => {
    const store = useShopbuddyStore();
    const folderPath = args[0];
    
    if (!folderPath || folderPath.trim() === '') {
        store.setPrefix(pageId, 'notemate file-watcher start new');
        store.addOutput('Enter Folder Path', pageId);
        return;
    }
    
    // Reset prefix back to default after getting path
    store.setPrefix(pageId, 'notemate');
    store.addOutput(`Starting file watcher for path: ${folderPath}`, pageId);
    
    // Save folder path to database
    router.post('/notemate/codefolders', {
        path: folderPath,
        is_working: true
    }, {
        onSuccess: () => {
            store.addOutput('Folder path saved to database', pageId);
            store.addOutput('File watcher is now monitoring changes', pageId);
        },
        onError: () => {
            store.addOutput('Error saving folder path to database', pageId);
        }
    });
};