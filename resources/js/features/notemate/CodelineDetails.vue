<script setup>
import { ref, onMounted, computed } from 'vue';
import { Tag, Hash, FileText } from 'lucide-vue-next';

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
    } catch (err) {
        error.value = err.message;
        console.error('Error fetching codelines:', err);
    } finally {
        loading.value = false;
    }
};

// Get codelines with purpose keys or variables, sorted by line number
const relevantCodelines = computed(() => {
    return codelines.value
        .filter(line => line.purpose_key || line.variables)
        .sort((a, b) => (a.line_number || 0) - (b.line_number || 0));
});

// Get appropriate icon for purpose key
const getPurposeIcon = (purposeKey) => {
    const icons = {
        'import': '📦',
        'component': '🧩',
        'method': '⚙️',
        'variable': '📝',
        'comment': '💬',
        'template': '🎨',
        'style': '🎨',
        'script': '📜',
        'export': '📤',
        'function': '🔧',
        'class': '🏗️',
        'interface': '📋',
        'type': '🏷️'
    };
    return icons[purposeKey] || '📄';
};

// Get purpose key color
const getPurposeColor = (purposeKey) => {
    const colors = {
        'import': 'bg-blue-100 text-blue-800',
        'component': 'bg-purple-100 text-purple-800',
        'method': 'bg-green-100 text-green-800',
        'variable': 'bg-yellow-100 text-yellow-800',
        'comment': 'bg-gray-100 text-gray-800',
        'template': 'bg-pink-100 text-pink-800',
        'style': 'bg-indigo-100 text-indigo-800',
        'script': 'bg-orange-100 text-orange-800',
        'export': 'bg-teal-100 text-teal-800',
        'function': 'bg-emerald-100 text-emerald-800',
        'class': 'bg-violet-100 text-violet-800',
        'interface': 'bg-cyan-100 text-cyan-800',
        'type': 'bg-slate-100 text-slate-800'
    };
    return colors[purposeKey] || 'bg-gray-100 text-gray-600';
};

// Format line number with padding
const formatLineNumber = (lineNumber) => {
    return String(lineNumber || 0).padStart(3, '0');
};

onMounted(() => {
    fetchCodelines();
});
</script>

<template>
    <div class="h-full flex flex-col bg-white">
        <!-- Header -->
        <div class="flex-shrink-0 p-2 border-b border-gray-200">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-base font-semibold flex items-center gap-1">
                    <Tag :size="16" />
                    Purpose & Variables
                </h3>
                <button 
                    @click="fetchCodelines"
                    :disabled="loading"
                    class="px-1 py-0.5 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                >
                    {{ loading ? 'Loading...' : 'Refresh' }}
                </button>
            </div>
            <div class="text-xs text-gray-500">
                {{ relevantCodelines.length }} items
            </div>
        </div>
        
        <!-- Content area -->
        <div class="flex-1 overflow-y-auto">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center h-16">
                <div class="text-gray-500 text-xs">Loading...</div>
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
            <div v-else-if="relevantCodelines.length === 0" class="flex flex-col items-center justify-center h-16 text-gray-500 p-2">
                <FileText :size="24" class="mb-1" />
                <div class="text-xs text-center">No purpose keys or variables found</div>
            </div>
            
            <!-- Purpose & Variables List -->
            <div v-else class="divide-y divide-gray-100">
                <div 
                    v-for="codeline in relevantCodelines" 
                    :key="codeline.id"
                    class="p-2"
                >
                    <!-- Line Number -->
                    <div class="flex items-center gap-1 mb-1">
                        <Hash :size="12" class="text-gray-400" />
                        <span class="font-mono text-xs font-bold text-blue-600">
                            {{ formatLineNumber(codeline.line_number) }}
                        </span>
                    </div>
                    
                    <!-- Purpose Key -->
                    <div v-if="codeline.purpose_key" class="mb-1">
                        <span 
                            :class="getPurposeColor(codeline.purpose_key)"
                            class="inline-flex items-center gap-1 px-1 py-0.5 rounded text-xs font-medium"
                        >
                            {{ getPurposeIcon(codeline.purpose_key) }}
                            {{ codeline.purpose_key }}
                        </span>
                    </div>
                    
                    <!-- Variables -->
                    <div v-if="codeline.variables" class="bg-yellow-50 border border-yellow-200 p-1 rounded text-xs">
                        <div class="font-medium text-yellow-800">Variables:</div>
                        <div class="font-mono text-yellow-900">{{ codeline.variables }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>