import { ref, watch } from 'vue';
import { startFileWatcher, startFileWatcherNew } from './commands/startFileWatcher.js';
import { addSnippetFeature } from './commands/addSnippetFeature.js';
import { useShopbuddyStore } from './shopbuddy.store.js';
import { makeFiletree, makeFiletreeFromCodefolder, makeFiletreeIsWorking } from './commands/makeFiletree.js';
import { listFiletree, listFiletreeCount } from './commands/listFiletree.js';
/**
 * Command Registry
 * 
 * Defines the available command structure for the Shopbuddy terminal.
 * Commands are organized hierarchically: sb -> prefix -> command -> subcommand
 */
const commandRegistry = {
    sb: {
        notemate: {
            handler: addSnippetFeature, // Default handler for "notemate [text]"
            'file-watcher': {
                start: {
                    handler: startFileWatcher,
                    new: {
                        handler: startFileWatcherNew
                    }
                }
            },
            'cd': {
                handler: (args, pageId) => {
                    const store = useShopbuddyStore();
                    store.addOutput('Changing directory...', pageId);
                }
            },
            make: {
                filetree: {
                    handler: makeFiletree,
                    "from-codefolder": { 
                        handler: makeFiletreeFromCodefolder 
                    },
                    "is_working": { 
                        handler: makeFiletreeIsWorking 
                    }
                }
            },
            list: {
                filetree: { 
                    handler: listFiletree 
                }
            }
        },
        make: {
            filetree: {
                handler: makeFiletree,
                "from-codefolder": { 
                    handler: makeFiletreeFromCodefolder 
                },
                "is_working": { 
                    handler: makeFiletreeIsWorking 
                }
            }
        },
        list: {
            filetree: { 
                handler: listFiletree 
            }
        }
    }
};

/**
 * @returns {Object} An object containing the handleMessage function
 */
export function useShopbuddy() {
    const store = useShopbuddyStore();
    
    /**
     * Parse and execute commands from the Shopbuddy terminal
     * 
     * This function parses the command structure: sb [prefix] [command] [subcommand]
     * and routes to the appropriate handler in the command registry.
     * 
     * @param {string} root - Always "sb" for Shopbuddy commands
     * @param {string} prefix - The terminal prefix/identifier (e.g., 'notemate')
     * @param {string} commandString - The command and arguments entered by user
     */
    const parseMessage = (root, prefix, commandString, pageId) => {
        
        // Split the command string into parts
        const commandParts = commandString.trim().split(/\s+/);
        
        // Navigate through the command registry
        let currentLevel = commandRegistry[root];
        if (!currentLevel) {
            store.addOutput(`Unknown root command: ${root}`, pageId);
            return;
        }
        
        // Check if the first command part exists directly under root (no prefix needed)
        if (commandParts.length > 0 && currentLevel[commandParts[0]]) {
            // Direct command under root, no prefix needed
            // Skip prefix lookup and use the command directly
        } else if (prefix && prefix.trim() !== '') {
            // Traditional prefix-based command lookup (only if prefix is not empty)
            currentLevel = currentLevel[prefix];
            if (!currentLevel) {
                store.addOutput(`Unknown prefix: ${prefix}`, pageId);
                return;
            }
        }
        // If prefix is empty, stay at root level (currentLevel = commandRegistry[root])
        
        // Navigate through command parts, but separate command navigation from arguments
        let commandIndex = 0;
        
        // If we're using a direct command (no prefix), start navigation from the root level
        if (commandParts.length > 0 && commandRegistry[root][commandParts[0]]) {
            currentLevel = commandRegistry[root];
        }
        
        for (const part of commandParts) {
            const nextLevel = currentLevel[part];
            if (!nextLevel) {
                // If we can't find the next level, this might be where arguments start
                break;
            }
            currentLevel = nextLevel;
            commandIndex++;
        }
        
        // Extract remaining parts as arguments
        const args = commandParts.slice(commandIndex);
        
        // Execute the handler if we found one
        if (currentLevel.handler && typeof currentLevel.handler === 'function') {
            // Get the actual pageId from the prefix (first word)
            const actualPageId = prefix.split(' ')[0];
            currentLevel.handler(args, actualPageId);
        } else {
            // Special handling for compound prefixes
            if ((prefix.startsWith('list filetree ') && prefix.split(' ').length > 2) ||
                (prefix.startsWith('notemate list filetree ') && prefix.split(' ').length > 3)) {
                // This is a count selection for a specific project
                listFiletreeCount(commandParts, pageId);
            } else if (prefix === 'make filetree' || prefix === 'notemate make filetree') {
                // This is path input for make filetree command
                makeFiletree(commandParts, pageId);
            } else {
                store.addOutput('No handler found for command', pageId);
            }
        }
    };

    /**
     * Handles messages entered in the Shopbuddy terminal
     * 
     * This function is called when a user enters text and presses Enter in the terminal.
     * It routes the message to parseMessage for command processing.
     * 
     * @param {string} prefix - The terminal prefix/identifier (e.g., 'notemate')
     * @param {string} message - The actual text/command entered by the user
     */
    const handleMessage = (prefix, message) => {
        // Check if prefix contains a full command path (e.g., "notemate file-watcher start new")
        const prefixParts = prefix.split(' ');
        const pageId = prefixParts[0]; // Always use first part as pageId
        
        if (prefixParts.length > 1) {
            // Prefix contains command path, combine with message
            const basePrefix = prefixParts[0]; // 'notemate'
            const commandPath = prefixParts.slice(1).join(' '); // 'file-watcher start new'
            const fullCommand = commandPath + ' ' + message;
            parseMessage('sb', basePrefix, fullCommand, pageId);
        } else {
            // Normal prefix, parse as before
            parseMessage('sb', prefix, message, pageId);
        }
    };

    // Return the handleMessage function for use in components
    return {
        handleMessage 
    };
}