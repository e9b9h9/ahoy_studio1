<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ArrowLeft, BookOpen, Play } from 'lucide-vue-next';

const props = defineProps({
    ebook: Object,
    currentSection: Object,
    sections: Array,
    readingSession: Object,
    navigation: Object,
});

const showSectionSelector = ref(false);

const progressPercentage = computed(() => {
    return Math.round((props.navigation.current / props.navigation.total) * 100);
});

const sectionTypeDisplay = computed(() => {
    const types = {
        'chapter': 'Chapter',
        'page': 'Page', 
        'section': 'Section',
        'segment': 'Segment',
        'article': 'Article',
        'paragraph': 'Paragraph'
    };
    return types[props.currentSection.section_type] || 'Section';
});

const readingTime = computed(() => {
    // Estimate reading time at 200 words per minute
    const minutes = Math.ceil(props.currentSection.word_count / 200);
    return minutes === 1 ? '1 min read' : `${minutes} min read`;
});
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white border-b sticky top-0 z-10">
                <div class="max-w-4xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Back to library -->
                        <Link 
                            :href="'/ebook-reader'" 
                            class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <ArrowLeft :size="20" />
                            <span>Back to Library</span>
                        </Link>

                        <!-- Book info -->
                        <div class="text-center">
                            <h1 class="font-semibold text-lg">{{ ebook.title }}</h1>
                            <p class="text-sm text-gray-600">by {{ ebook.author || 'Unknown Author' }}</p>
                        </div>

                        <!-- Generate Audio button (placeholder) -->
                        <button class="flex items-center gap-2 px-3 py-1.5 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            <Play :size="16" />
                            <span>Generate Audio</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="bg-white border-b">
                <div class="max-w-4xl mx-auto px-6 py-2">
                    <div class="flex items-center gap-4">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div 
                                class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                        <span class="text-sm text-gray-600 min-w-fit">
                            {{ progressPercentage }}% complete
                        </span>
                    </div>
                </div>
            </div>

            <!-- Section navigation -->
            <div class="bg-white border-b">
                <div class="max-w-4xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Previous button -->
                        <Link 
                            v-if="navigation.previous"
                            :href="`/ebook-reader/${ebook.id}/read/${navigation.previous}`"
                            class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                        >
                            <ChevronLeft :size="16" />
                            <span>Previous</span>
                        </Link>
                        <div v-else class="w-20"></div>

                        <!-- Section info -->
                        <div class="text-center">
                            <div class="relative">
                                <button 
                                    @click="showSectionSelector = !showSectionSelector"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                >
                                    {{ sectionTypeDisplay }} {{ navigation.current }} of {{ navigation.total }}
                                </button>
                                
                                <!-- Section dropdown -->
                                <div 
                                    v-if="showSectionSelector" 
                                    class="absolute top-full mt-2 left-1/2 transform -translate-x-1/2 bg-white border rounded-lg shadow-lg z-20 min-w-64 max-h-80 overflow-y-auto"
                                >
                                    <Link
                                        v-for="section in sections"
                                        :key="section.id"
                                        :href="`/ebook-reader/${ebook.id}/read/${section.section_number}`"
                                        @click="showSectionSelector = false"
                                        :class="[
                                            'block px-4 py-2 text-left hover:bg-gray-50 transition-colors',
                                            section.section_number === navigation.current ? 'bg-blue-50 text-blue-700' : ''
                                        ]"
                                    >
                                        <div class="font-medium">
                                            {{ section.section_type === 'chapter' ? 'Chapter' : 'Section' }} {{ section.section_number }}
                                        </div>
                                        <div v-if="section.title" class="text-sm text-gray-600">
                                            {{ section.title }}
                                        </div>
                                    </Link>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">{{ readingTime }}</p>
                        </div>

                        <!-- Next button -->
                        <Link 
                            v-if="navigation.next"
                            :href="`/ebook-reader/${ebook.id}/read/${navigation.next}`"
                            class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                        >
                            <span>Next</span>
                            <ChevronRight :size="16" />
                        </Link>
                        <div v-else class="w-20"></div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-4xl mx-auto px-6 py-8">
                <div class="bg-white rounded-lg shadow-sm border p-8">
                    <!-- Section title -->
                    <div v-if="currentSection.title" class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">{{ currentSection.title }}</h2>
                    </div>

                    <!-- Section content -->
                    <div class="prose prose-lg max-w-none">
                        <div class="whitespace-pre-wrap leading-relaxed text-gray-800 text-lg">
                            {{ currentSection.content }}
                        </div>
                    </div>

                    <!-- Section footer info -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>{{ currentSection.word_count.toLocaleString() }} words</span>
                            <span>{{ currentSection.character_count.toLocaleString() }} characters</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom navigation (mobile-friendly) -->
            <div class="bg-white border-t sticky bottom-0">
                <div class="max-w-4xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-between">
                        <Link 
                            v-if="navigation.previous"
                            :href="`/ebook-reader/${ebook.id}/read/${navigation.previous}`"
                            class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
                        >
                            <ChevronLeft :size="16" />
                            <span>Previous</span>
                        </Link>
                        <div v-else></div>

                        <span class="text-sm text-gray-600">
                            {{ navigation.current }} / {{ navigation.total }}
                        </span>

                        <Link 
                            v-if="navigation.next"
                            :href="`/ebook-reader/${ebook.id}/read/${navigation.next}`"
                            class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
                        >
                            <span>Next</span>
                            <ChevronRight :size="16" />
                        </Link>
                        <div v-else></div>
                    </div>
                </div>
            </div>

            <!-- Click outside to close dropdown -->
            <div 
                v-if="showSectionSelector" 
                @click="showSectionSelector = false"
                class="fixed inset-0 z-10"
            ></div>
        </div>
    </AppLayout>
</template>