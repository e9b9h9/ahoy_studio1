<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload, FileText, X, Plus } from 'lucide-vue-next';

const props = defineProps({
    uploadUrl: {
        type: String,
        default: '/ebook-reader/upload'
    }
});

const isDragging = ref(false);
const fileInput = ref(null);

const form = useForm({
    file: null,
    title: '',
    author: ''
});

const selectedFile = ref(null);

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        selectFile(files[0]);
    }
};

const selectFile = (file) => {
    // Check file type
    const allowedTypes = ['text/plain', 'application/pdf', 'application/epub+zip'];
    const allowedExtensions = ['txt', 'pdf', 'epub'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
        alert('Please select a valid file type (TXT, PDF, or EPUB)');
        return;
    }
    
    // Check file size (50MB max)
    if (file.size > 50 * 1024 * 1024) {
        alert('File size must be less than 50MB');
        return;
    }
    
    selectedFile.value = file;
    form.file = file;
    
    // Extract title from filename
    const nameWithoutExtension = file.name.replace(/\.[^/.]+$/, '');
    form.title = nameWithoutExtension;
};

const handleFileSelect = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        selectFile(files[0]);
    }
};

const removeFile = () => {
    selectedFile.value = null;
    form.file = null;
    form.title = '';
    form.author = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const uploadFile = () => {
    if (!form.file) {
        alert('Please select a file');
        return;
    }
    
    form.post(props.uploadUrl, {
        preserveScroll: true,
        onSuccess: () => {
            removeFile();
        },
        onError: (errors) => {
            console.error('Upload failed:', errors);
        }
    });
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>
    <div class="w-full max-w-lg mx-auto">
        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
                'relative border border-dashed rounded-lg transition-all duration-200',
                isDragging ? 'border-blue-400 bg-blue-50/50' : 'border-gray-300 hover:border-gray-400',
                selectedFile ? 'p-4' : 'p-6'
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                @change="handleFileSelect"
                accept=".txt,.pdf,.epub"
                class="hidden"
            />
            
            <!-- Upload State -->
            <div v-if="!selectedFile" class="text-center">
                <Upload :size="32" class="mx-auto mb-3 text-gray-400" />
                <p class="text-sm font-medium mb-1">Drop ebook or</p>
                <button
                    @click="$refs.fileInput.click()"
                    type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                >
                    <Plus :size="14" />
                    Browse
                </button>
                <p class="text-xs text-gray-500 mt-2">TXT, PDF, EPUB • Max 50MB</p>
            </div>
            
            <!-- File Selected State -->
            <div v-else class="space-y-3">
                <!-- File Info -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded border">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <FileText :size="18" class="text-gray-600 flex-shrink-0" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium truncate">{{ selectedFile.name }}</p>
                            <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                    </div>
                    <button
                        @click="removeFile"
                        type="button"
                        class="p-1 hover:bg-gray-200 rounded transition-colors flex-shrink-0"
                        title="Remove file"
                    >
                        <X :size="16" />
                    </button>
                </div>
                
                <!-- Form Fields -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Book title"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Author</label>
                        <input
                            v-model="form.author"
                            type="text"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Optional"
                        />
                    </div>
                </div>
                
                <!-- Upload Button -->
                <button
                    @click="uploadFile"
                    :disabled="form.processing"
                    class="w-full py-2 text-sm font-medium bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    {{ form.processing ? 'Uploading...' : 'Upload Ebook' }}
                </button>
            </div>
        </div>
        
        <!-- Error Message -->
        <div v-if="form.errors.file" class="mt-2 text-red-500 text-xs">
            {{ form.errors.file }}
        </div>
    </div>
</template>