<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload, FileText, X } from 'lucide-vue-next';

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
    <div class="w-full max-w-2xl mx-auto p-6">
        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            :class="[
                'relative border-2 border-dashed rounded-lg p-8 transition-colors',
                isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                @change="handleFileSelect"
                accept=".txt,.pdf,.epub"
                class="hidden"
            />
            
            <div v-if="!selectedFile" class="text-center">
                <Upload :size="48" class="mx-auto mb-4 text-gray-400" />
                <p class="text-lg mb-2">Drop your ebook here</p>
                <p class="text-sm text-gray-500 mb-4">or</p>
                <button
                    @click="$refs.fileInput.click()"
                    type="button"
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                >
                    Browse Files
                </button>
                <p class="text-xs text-gray-500 mt-4">Supported formats: TXT, PDF, EPUB (max 50MB)</p>
            </div>
            
            <div v-else class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <FileText :size="32" class="text-gray-600" />
                        <div>
                            <p class="font-medium">{{ selectedFile.name }}</p>
                            <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                    </div>
                    <button
                        @click="removeFile"
                        type="button"
                        class="p-1 hover:bg-gray-200 rounded"
                    >
                        <X :size="20" />
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter book title"
                        />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Author (optional)</label>
                        <input
                            v-model="form.author"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter author name"
                        />
                    </div>
                </div>
                
                <button
                    @click="uploadFile"
                    :disabled="form.processing"
                    class="w-full py-2 bg-green-500 text-white rounded-md hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Uploading...' : 'Upload Ebook' }}
                </button>
            </div>
        </div>
        
        <div v-if="form.errors.file" class="mt-2 text-red-500 text-sm">
            {{ form.errors.file }}
        </div>
    </div>
</template>