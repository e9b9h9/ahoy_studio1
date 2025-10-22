<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Code, FileText } from 'lucide-vue-next';
import { useVariableHighlighting } from './useVariableHighlighting.js';
import { useWordClick } from './useWordClick.js';
import { useVariableSelection } from './useVariableSelection.js';

const codelines = ref([]);
const loading = ref(false);
const error = ref(null);

// Fetch codelines from API
const fetchCodelines = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await fetch('/api/notemate/temp-codelines');
        if (!response.ok) {
            throw new Error('Failed to fetch codelines');
        }
        const data = await response.json();
        codelines.value = data.codelines || [];
        
        // Fetch variables from database to know what to highlight
        await fetchVariables();
        
        // Collect variables from temp_codelines to catch missing ones
        collectTempCodelineVariables(codelines.value);
    } catch (err) {
        error.value = err.message;
        console.error('Error fetching codelines:', err);
    } finally {
        loading.value = false;
    }
};




// Use variable highlighting composable
const { 
    variables, 
    fetchVariables,
    collectTempCodelineVariables,
    isTrackedVariable, 
    parseCodeIntoWords, 
    getVariableColor,
    getVariableDetails,
    isMissingVariable
} = useVariableHighlighting();

// Use word click composable
const { handleWordClick: baseHandleWordClick } = useWordClick();

// Use variable selection composable
const { selectedText, isSelecting } = useVariableSelection();

// Format line number with padding
const formatLineNumber = (lineNumber) => {
    return String(lineNumber || 0).padStart(3, '0');
};

// Wrapper for word click to pass additional context
const handleWordClick = (part, codeline) => {
    baseHandleWordClick(part, codeline, isTrackedVariable, getVariableColor, isMissingVariable);
};

// Listen for variable additions to refresh the display
const handleVariableAdded = async () => {
    console.log('Variable added, refreshing highlights...');
    await fetchVariables();
    collectTempCodelineVariables(codelines.value);
};

onMounted(async () => {
    await fetchCodelines();
    
    // Listen for variable addition events
    window.addEventListener('variable-added', handleVariableAdded);
});

// Clean up event listener
onUnmounted(() => {
    window.removeEventListener('variable-added', handleVariableAdded);
});
</script>

<template>
    <div class="h-full flex flex-col bg-white">
        <!-- Header -->
        <div class="flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-semibold flex items-center gap-1">
                    <Code :size="16" />
                    Processed Codelines
                </h3>
                <div class="flex items-center gap-2">
                    <!-- Selection indicator -->
                    <div v-if="isSelecting" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded border">
                        "{{ selectedText }}" selected - Alt+A to add
                    </div>
                    <button 
                        @click="fetchCodelines"
                        :disabled="loading"
                        class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                    >
                        {{ loading ? 'Loading...' : 'Refresh' }}
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center h-16">
                <div class="text-gray-500 text-sm">Loading...</div>
            </div>
            
            <!-- Error State -->
            <div v-else-if="error" class="p-2">
                <div class="bg-red-50 border border-red-200 rounded p-2">
                    <div class="text-red-800 text-xs">
                        Error: {{ error }}
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else-if="codelines.length === 0" class="flex flex-col items-center justify-center h-16 text-gray-500">
                <FileText :size="24" class="mb-1" />
                <div class="text-xs">No codelines processed yet</div>
            </div>
            
            <!-- Codelines List -->
            
            <div v-else class="divide-y divide-gray-100">
                <div 
                    v-for="codeline in codelines" 
                    :key="codeline.id"
                    :data-codeline-id="codeline.id"
                    class="p-0 hover:bg-gray-50 transition-colors"
                >
                    <!-- Simple Codeline Row -->
                    <div class="p-2">
                        <!-- Line Number and Code -->
                        <div class="flex items-start gap-2">
                            <!-- Line Number -->
                            <div class="flex-shrink-0 text-xs font-mono text-gray-400 bg-gray-100 px-1 py-0.5 rounded">
                                {{ formatLineNumber(codeline.line_number) }}
                            </div>
                            
                            <!-- Code Content -->
                            <div class="flex-1 min-w-0">
                                <div class="font-mono text-xs text-gray-900 whitespace-pre-wrap">
                                    <template v-for="part in parseCodeIntoWords(codeline.codeline)" :key="part.key">
                                        <span 
                                            v-if="part.isVariable"
                                            @click="handleWordClick(part, codeline)"
                                            :class="part.variableColor"
                                            :title="part.isMissingVariable ? 'Variable found in codeline but not in database' : 'Tracked variable'"
                                            class="cursor-pointer rounded px-1 py-0.5 border transition-all font-medium"
                                        >{{ part.text }}</span>
                                        <span 
                                            v-else-if="part.isClickable"
                                            @click="handleWordClick(part, codeline)"
                                            class="cursor-pointer hover:bg-gray-100 rounded px-0.5 transition-colors"
                                        >{{ part.text }}</span>
                                        <span v-else>{{ part.text }}</span>
                                    </template>
                                    <span v-if="!codeline.codeline">No content</span>
                                </div>
                                <div v-if="codeline.comment" class="text-xs text-gray-600">
                                    💬 {{ codeline.comment }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>