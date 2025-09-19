<script setup>
import { Button } from '@/components/ui/button';
import { PanelLeft, PanelRight } from 'lucide-vue-next';
import { ref } from 'vue';

const leftSidebarOpen = ref(true);
const rightSidebarOpen = ref(true);

const toggleLeftSidebar = () => {
    leftSidebarOpen.value = !leftSidebarOpen.value;
};

const toggleRightSidebar = () => {
    rightSidebarOpen.value = !rightSidebarOpen.value;
};
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Header Bar with Toggle Buttons -->
        <header class="flex h-14 items-center gap-4 border-b border-border bg-background px-4">
            <div class="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="toggleLeftSidebar"
                    :aria-label="leftSidebarOpen ? 'Hide left sidebar' : 'Show left sidebar'"
                >
                    <PanelLeft class="h-4 w-4" />
                </Button>
                <span class="text-sm font-medium text-muted-foreground">Toggle Panels</span>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="toggleRightSidebar"
                    :aria-label="rightSidebarOpen ? 'Hide right sidebar' : 'Show right sidebar'"
                >
                    <PanelRight class="h-4 w-4" />
                </Button>
            </div>
        </header>

        <!-- Content Area with Sidebars -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Left Sidebar -->
            <aside 
                v-if="leftSidebarOpen"
                class="w-1/4 border-r border-border bg-muted/10 p-4 transition-all duration-300"
            >
                <h3 class="mb-4 text-sm font-semibold text-muted-foreground">Changed Files</h3>
                <div class="space-y-2">
                    <!-- File changes will go here -->
                    <slot name="left-sidebar">
                        <p class="text-sm text-muted-foreground">No files changed</p>
                    </slot>
                </div>
            </aside>

            <!-- Center Content -->
            <main class="flex-1 overflow-auto p-6">
                <h2 class="mb-4 text-2xl font-bold">Notemate</h2>
                <div class="space-y-4">
                    <!-- Main content area -->
                    <slot name="main-content">
                        <p class="text-muted-foreground">Select a file to view comments</p>
                    </slot>
                </div>
            </main>

            <!-- Right Sidebar -->
            <aside 
                v-if="rightSidebarOpen"
                class="w-1/4 border-l border-border bg-muted/10 p-4 transition-all duration-300 flex flex-col"
            >
                <h3 class="mb-4 text-sm font-semibold text-muted-foreground">Features</h3>
                <div class="flex-1 space-y-2">
                    <!-- Features list will go here -->
                    <slot name="right-sidebar-top">
                        <p class="text-sm text-muted-foreground">No features tracked</p>
                    </slot>
                </div>
                
                <!-- Shopbuddy Terminal at bottom -->
                <div class="mt-4 pt-4 border-t border-border">
                    <slot name="right-sidebar-bottom" />
                </div>
            </aside>
        </div>

        <!-- Shopbuddy Terminal at bottom -->
        <div class="mt-4 pt-4 border-t border-border">
            <slot name="terminal" />
        </div>
    </div>
</template>