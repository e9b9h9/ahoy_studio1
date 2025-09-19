<script setup>
import { ref, watch, nextTick, computed } from 'vue';
import { useShopbuddyStore } from './shopbuddy.store.js';

const props = defineProps({
  instanceId: {
    type: String,
    default: 'default'
  }
});

const store = useShopbuddyStore();
const outputContainer = ref(null);

// Get messages for this specific instance
const messages = computed(() => store.getOutputMessages(props.instanceId));

// Auto-scroll to bottom when new messages are added
watch(
  messages,
  async () => {
    await nextTick();
    if (outputContainer.value) {
      outputContainer.value.scrollTop = outputContainer.value.scrollHeight;
    }
  },
  { deep: true }
);
</script>

<template>
    <div 
        ref="outputContainer"
        class="h-48 overflow-y-auto font-mono text-sm space-y-1 p-2 bg-gray-900/50 rounded"
    >
        <div 
          v-for="message in messages" 
          :key="message.id"
          class="text-green-400"
        >
          {{ message.text }}
        </div>
    </div>
</template>