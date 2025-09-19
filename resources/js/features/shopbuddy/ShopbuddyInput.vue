<script setup>
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

/**
 * Component Props
 * 
 * @prop {string} prefix - The terminal prompt prefix to display
 *                         Default: '' (empty string)
 */
defineProps({
  prefix: {
    type: String,
    default: ''
  }
});

/**
 * Component Events
 * 
 * @emits message - Emitted when user presses Enter with non-empty input
 *                  Payload: {string} The trimmed text entered by the user
 */
const emit = defineEmits(['message']);

// Reactive reference to store the current input value
// This is bound to the input field via v-model
const inputValue = ref('');

const handleEnter = () => {
  // Only process if input has actual content (not just whitespace)
  if (inputValue.value.trim()) {
    // Emit the message event with trimmed input value
    // The parent component will handle the actual command processing
    emit('message', inputValue.value.trim());
    
    // Clear the input field after submission for next command
    inputValue.value = '';
  }
  // If input is empty or only whitespace, do nothing
};

</script>

<template>
    <!-- Container div with flexbox for horizontal layout -->
    <div class="flex items-center space-x-2">

        <span class="text-green-500 font-mono">{{ prefix }}</span>
      
        <Input
            v-model="inputValue"
            @keydown.enter="handleEnter"
            class="flex-1 bg-transparent border-none focus:ring-0 font-mono text-sm"
            :class="{
                'text-white': false,
                'text-gray-300': true
            }"
        />
    </div>
</template>