<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const workingFolder = ref(null);
const workingSnippetfeature = ref(null);
const loading = ref(true);

const fetchWorkingFolder = async () => {
    try {
        const response = await axios.get('/notemate/codefolders');
        const folders = response.data;
        
        // Find the folder with is_working = true
        workingFolder.value = folders.find(folder => folder.is_working);
        loading.value = false;
    } catch (error) {
        console.error('Error fetching codefolders:', error);
        loading.value = false;
    }
};

const fetchWorkingSnippetfeature = async () => {
    try {
        const response = await axios.get('/notemate/snippetfeatures');
        const snippetfeatures = response.data;
        
        // Find the snippet feature with is_working = true
        workingSnippetfeature.value = snippetfeatures.find(feature => feature.is_working);
        loading.value = false;
    } catch (error) {
        console.error('Error fetching snippetfeatures:', error);
        loading.value = false;
    }
};

onMounted(() => {
    fetchWorkingFolder();
    fetchWorkingSnippetfeature();
    // Refresh every 5 seconds to catch updates
    const interval = setInterval(fetchWorkingFolder, 5000);
    const intervalSnippetfeature = setInterval(fetchWorkingSnippetfeature, 5000);
    // Cleanup on unmount
    return () => clearInterval(interval);
});
</script>

<template>
    <!-- Memory Panel-->
    <div class="flex flex-col h-full bg-gray-950 rounded-lg border border-gray-800">
        <div class="p-3">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Active Folder
            </h3>
            
            <div v-if="loading" class="text-gray-500 text-sm">
                Loading...
            </div>
            
            <div v-else-if="workingFolder" class="space-y-2">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-gray-300 text-sm font-mono truncate">
                        {{ workingFolder.path }}
                    </span>
                </div>

            </div>
            
            <div v-else class="text-gray-500 text-sm">
                No active folder
            </div>
						<h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                Active Snippetfeature
            </h3>
            
            <div v-if="loading" class="text-gray-500 text-sm">
                Loading...
            </div>
            
            <div v-else-if="workingSnippetfeature" class="space-y-2">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-gray-300 text-sm font-mono truncate">
                        {{ workingSnippetfeature.snippetfeature }}
                    </span>
                </div>

            </div>
            
            <div v-else class="text-gray-500 text-sm">
                No Active Snippetfeature
            </div>
        </div>
    </div>
</template>