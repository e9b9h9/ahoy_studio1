<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const fileChanges = ref([]);
const loading = ref(true);
let intervalId = null;

const groupedChanges = computed(() => {
    const groups = {};
    
    fileChanges.value.forEach(change => {
        const folderPath = change.folder_path || 'Root';
        
        if (!groups[folderPath]) {
            groups[folderPath] = [];
        }
        
        groups[folderPath].push(change);
    });
    
    // Sort folders alphabetically, with Root first
    const sortedGroups = {};
    const sortedKeys = Object.keys(groups).sort((a, b) => {
        if (a === 'Root') return -1;
        if (b === 'Root') return 1;
        return a.localeCompare(b);
    });
    
    sortedKeys.forEach(key => {
        sortedGroups[key] = groups[key];
    });
    
    return sortedGroups;
});

const fetchFileChanges = async () => {
    try {
        const response = await axios.get('/notemate/file-changes');
        fileChanges.value = response.data;
        loading.value = false;
    } catch (error) {
        console.error('Error fetching file changes:', error);
        loading.value = false;
    }
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString();
};

const getChangeColor = (changeType) => {
    switch (changeType) {
        case 'new': return 'text-green-600';
        case 'modified': return 'text-blue-600';
        case 'deleted': return 'text-red-600';
        default: return 'text-gray-600';
    }
};

const openFile = async (change) => {
    console.log('Processing file:', change.file_path);
    
    try {
        // Get the active codefolder to build the full path
        const foldersResponse = await axios.get('/notemate/codefolders');
        const activeFolder = foldersResponse.data.find(folder => folder.is_working);
        
        if (!activeFolder) {
            console.error('No active folder found');
            return;
        }
        
        // Build the full file path
        const fullPath = `${activeFolder.path}\\${change.file_path}`;
        
        // First, create the framefile
        const framefileResponse = await axios.post('/notemate/framefiles/process', {
            file_path: fullPath,
            file_name: change.file_name
        });
        
        console.log('Framefile created:', framefileResponse.data);
        
        // Then, process the codelines
        const codelinesResponse = await axios.post('/notemate/codelines/process', {
            file_path: fullPath
        });
        
        console.log('Codelines processed:', codelinesResponse.data);
        console.log(`Framefile ID: ${framefileResponse.data.framefile_id}, Lines processed: ${codelinesResponse.data.lines_processed}`);
    } catch (error) {
        console.error('Error processing file:', error);
        if (error.response?.data?.message) {
            console.error('Error message:', error.response.data.message);
        }
    }
};

onMounted(() => {
    fetchFileChanges();
    // Refresh every 5 seconds to get new changes
    intervalId = setInterval(fetchFileChanges, 5000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script> 

<template>
    <div class="bg-white rounded-lg border p-2">
        <h3 class="text-lg font-semibold mb-2">Recent File Changes</h3>
        
        <div v-if="loading" class="text-center text-gray-500">
            Loading file changes...
        </div>
        
        <div v-else-if="fileChanges.length === 0" class="text-center text-gray-500">
            No file changes detected yet.
        </div>
        
        <div v-else class="max-h-96 overflow-y-auto">
            <div v-for="(files, folderPath) in groupedChanges" :key="folderPath" class="mb-4">
                <div class="text-xs text-gray-600 font-semibold border-l-2 border-gray-300">
                    {{ folderPath }}
                </div>
                <div 
                    v-for="change in files" 
                    :key="change.id"
                    class="flex items-center bg-gray-50 rounded "
                >
                    <button 
                        v-if="change.change_type !== 'deleted'"
                        @click="openFile(change)"
                        :class="[
                            'font-mono text-sm cursor-pointer px-2 py-1 rounded-md transition-all duration-150',
                            'bg-gradient-to-b from-white to-gray-100 border border-gray-300',
                            'shadow-[0_2px_4px_rgba(0,0,0,0.1),inset_0_1px_0_rgba(255,255,255,0.5)]',
                            'hover:from-gray-50 hover:to-gray-200 hover:shadow-[0_1px_2px_rgba(0,0,0,0.2),inset_0_1px_0_rgba(255,255,255,0.6)]',
                            'active:shadow-[inset_0_2px_4px_rgba(0,0,0,0.2)] active:from-gray-200 active:to-gray-100',
                            getChangeColor(change.change_type)
                        ]"
                    >
                        {{ change.file_name }}
                    </button>
                    <div 
                        v-else
                        :class="['font-mono text-sm', getChangeColor(change.change_type)]"
                    >
                        {{ change.file_name }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>




