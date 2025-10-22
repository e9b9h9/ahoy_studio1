<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import NotemateLayout from '@/features/notemate/NotemateLayout.vue';
import NotemateMemory from '@/features/notemate/NotemateMemory.vue';
import FileChanges from '@/features/notemate/FileChanges.vue';
import CodelineProcessor from '@/features/notemate/CodelineProcessor.vue';
import CodelineDetails from '@/features/notemate/CodelineDetails.vue';
import Shopbuddy from '@/features/shopbuddy/Shopbuddy.vue';
import { notemate } from '@/routes';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [
    {
        title: 'Notemate',
        href: notemate().url,

    },
];
</script>

<template>
    <Head title="Notemate" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <NotemateLayout>

            <!-- Left sidebar content slot -->
            <template #left-sidebar>
                <FileChanges />
            </template>

            <!-- Main content slot -->
            <template #main-content>
                <CodelineProcessor />
            </template>

            <!-- Right sidebar top slot -->
            <template #right-sidebar-top>
                <CodelineDetails />
            </template>

            <!-- Right sidebar bottom slot -->
            <template #right-sidebar-bottom>
                <NotemateMemory />
            </template>

						            <!-- Shopbuddy Terminal below toggle panels -->
						<template #terminal>
                <Shopbuddy id="notemate" />
            </template>
        </NotemateLayout>
    </AppLayout>
</template>
