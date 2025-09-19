import { useShopbuddyStore } from '../shopbuddy.store.js';
import axios from 'axios';

/**
 * List all available filetrees with their project names and counts
 * 
 * Command: sb list filetree [selection]
 * 
 * @param {string[]} args - Command arguments, optional selection number
 * @returns {void}
 */
export const listFiletree = async (args = [], pageId = 'default') => {
    const store = useShopbuddyStore();
    const selection = args[0];
    
    try {
        // If no selection provided, show all projects
        if (!selection || selection.trim() === '') {
            const response = await axios.get('/filetree');
            const projects = response.data;
            
            if (Object.keys(projects).length === 0) {
                store.addOutput('No filetrees found. Use "make filetree" to create one.', pageId);
                return;
            }
            
            // Store projects for later selection
            const projectList = Object.entries(projects).map(([name, counts]) => ({
                name,
                counts
            }));
            store.tempFiletreeProjects = projectList;
            
            store.addOutput('Available filetree projects:', pageId);
            store.addOutput('', pageId);
            
            projectList.forEach((project, index) => {
                const countDisplay = project.counts.map(c => c === null ? 'original' : c).join(', ');
                store.addOutput(`${index + 1}. ${project.name} (counts: ${countDisplay})`, pageId);
            });
            
            store.addOutput('', pageId);
            
            // Determine the full command path based on current context
            const currentPrefix = store.getInitialPrefix(pageId);
            const fullCommandPath = currentPrefix ? `${currentPrefix} list filetree` : 'list filetree';
            store.setPrefix(pageId, fullCommandPath);
            store.addOutput('Enter project number:', pageId);
            return;
        }
        
        // Handle project selection by number
        const projectNumber = parseInt(selection);
        const projects = store.tempFiletreeProjects || [];
        
        if (isNaN(projectNumber) || projectNumber < 1 || projectNumber > projects.length) {
            store.addOutput('Invalid selection. Please enter a valid project number.', pageId);
            return;
        }
        
        const selectedProject = projects[projectNumber - 1];
        
        // Reset prefix and clear temp data
        store.setPrefix(pageId, '');
        store.tempFiletreeProjects = null;
        
        // If project has multiple counts, show them for selection
        if (selectedProject.counts.length > 1) {
            store.tempSelectedProject = selectedProject;
            
            store.addOutput(`Available counts for "${selectedProject.name}":`, pageId);
            store.addOutput('', pageId);
            
            selectedProject.counts.forEach((count, index) => {
                const displayCount = count === null ? 'original' : count;
                store.addOutput(`${index + 1}. ${displayCount}`, pageId);
            });
            
            store.addOutput('', pageId);
            
            // Determine the full command path based on current context
            const currentPrefix = store.getInitialPrefix(pageId);
            const fullCommandPath = currentPrefix ? `${currentPrefix} list filetree ${selectedProject.name}` : `list filetree ${selectedProject.name}`;
            store.setPrefix(pageId, fullCommandPath);
            store.addOutput('Enter count number:', pageId);
            return;
        }
        
        // Only one count, show it directly
        const count = selectedProject.counts[0];
        await showFiletree(selectedProject.name, count, pageId, store);
        
    } catch (error) {
        store.addOutput('Error fetching filetrees from database', pageId);
        console.error('Filetree list error:', error);
    }
};

/**
 * Handle count selection for a project
 */
export const listFiletreeCount = async (args = [], pageId = 'default') => {
    const store = useShopbuddyStore();
    const selection = args[0];
    
    const selectedProject = store.tempSelectedProject;
    if (!selectedProject) {
        store.addOutput('Error: No project selected', pageId);
        return;
    }
    
    const countNumber = parseInt(selection);
    if (isNaN(countNumber) || countNumber < 1 || countNumber > selectedProject.counts.length) {
        store.addOutput('Invalid selection. Please enter a valid count number.', pageId);
        return;
    }
    
    const selectedCount = selectedProject.counts[countNumber - 1];
    
    // Reset prefix and clear temp data
    store.setPrefix(pageId, '');
    store.tempFiletreeProjects = null;
    store.tempSelectedProject = null;
    
    await showFiletree(selectedProject.name, selectedCount, pageId, store);
};

/**
 * Display the actual filetree
 */
async function showFiletree(projectName, count, pageId, store) {
    try {
        // Handle null count by converting to empty string for URL
        const countParam = count === null ? '' : count;
        const url = countParam === '' ? `/filetree/${projectName}` : `/filetree/${projectName}/${countParam}`;
        
        const response = await axios.get(url);
        const filetrees = response.data;
        
        if (filetrees.length === 0) {
            store.addOutput(`No filetree found for project "${projectName}" with count ${count}`, pageId);
            return;
        }
        
        const displayCount = count === null ? 'original' : count;
        store.addOutput(`Filetree for "${projectName}" (count: ${displayCount})`, pageId);
        store.addOutput('', pageId);
        
        // Create a map for quick parent-child lookup
        const itemsById = {};
        const childrenByParent = {};
        
        filetrees.forEach(item => {
            itemsById[item.id] = item;
            if (!childrenByParent[item.parent_id]) {
                childrenByParent[item.parent_id] = [];
            }
            childrenByParent[item.parent_id].push(item);
        });
        
        // Recursive function to display items with proper indentation
        function displayItem(item, level = 0, isLast = true, parentPrefix = '') {
            if (level > 10) return; // Limit to 10 levels
            
            let prefix = '';
            if (level === 0) {
                prefix = '';
            } else {
                // Create the tree structure
                const connector = isLast ? '└─' : '├─';
                prefix = parentPrefix + connector + ' ';
            }
            
            store.addOutput(`${prefix}${item.name}`, pageId);
            
            // Get children for this item
            const children = childrenByParent[item.id] || [];
            if (children.length > 0) {
                // Sort children: folders first, then alphabetically
                children.sort((a, b) => {
                    if (a.is_folder !== b.is_folder) {
                        return b.is_folder - a.is_folder; // folders first
                    }
                    return a.name.localeCompare(b.name);
                });
                
                children.forEach((child, index) => {
                    const isLastChild = index === children.length - 1;
                    const newParentPrefix = level === 0 ? '' : parentPrefix + (isLast ? '   ' : '│  ');
                    displayItem(child, level + 1, isLastChild, newParentPrefix);
                });
            }
        }
        
        // Start with root items (parent_id is null)
        const rootItems = filetrees.filter(item => item.parent_id === null);
        
        // Sort root items: folders first, then alphabetically
        rootItems.sort((a, b) => {
            if (a.is_folder !== b.is_folder) {
                return b.is_folder - a.is_folder; // folders first
            }
            return a.name.localeCompare(b.name);
        });
        
        rootItems.forEach((item, index) => {
            const isLast = index === rootItems.length - 1;
            displayItem(item, 0, isLast);
        });
        
        // Reset prefix and clear temp data
        store.setPrefix(pageId, '');
        store.tempFiletreeProjects = null;
        store.tempSelectedProject = null;
        
    } catch (error) {
        store.addOutput('Error fetching filetree details', pageId);
        console.error('Filetree detail error:', error);
    }
}