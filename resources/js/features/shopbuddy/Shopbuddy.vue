<script setup>
import ShopbuddyInput from './ShopbuddyInput.vue';
import ShopbuddyOutput from './ShopbuddyOutput.vue';
import { useShopbuddy } from './useShopbuddy.js';
import { useShopbuddyStore } from './shopbuddy.store.js';
import { computed } from 'vue';

const props = defineProps({ 
  id: {
    type: String,
    default: ''
  }
});

const store = useShopbuddyStore();
/* 
multiline
*/
// Get the current prefix from store, fallback to props.id
const currentPrefix = computed(() => store.getInitialPrefix(props.id));

const { handleMessage: logMessage } = useShopbuddy();

const handleMessage = (message) => {
  logMessage(currentPrefix.value, message);
};

</script>
 
<template>
    <!-- Main terminal container 
		 multiline
		 with dark theme styling -->
    <div class="flex flex-col h-full bg-gray-950 rounded-lg border border-gray-800">
        <!-- Output area - takes up remaining space with flex-1 -->
        <div class="flex-1 p-3">
            <!-- Displays command history and responses -->
            <ShopbuddyOutput :instance-id="props.id" />
        </div>
        <!-- Input area - fixed at bottom with border separator -->
        <div class="p-3 border-t border-gray-800">
            <ShopbuddyInput :prefix="currentPrefix" @message="handleMessage" /> <!-- :prefix - Shows the terminal identifier (e.g., "notemate") @message - Listens for Enter key press and emits the entered text -->
        </div>
    </div>
</template>