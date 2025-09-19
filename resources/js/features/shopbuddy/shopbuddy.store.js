import { defineStore } from 'pinia'

export const useShopbuddyStore = defineStore('shopbuddy', {
  state: () => ({
    prefixStorage: {},
    outputMessages: {}, // Changed to object to store messages per instance
    tempCodefolders: null, // For makeFiletree commands
				tempFiletreeProjects: null,      // For listFiletree project selection
				tempSelectedProject: null,        // For listFiletree count selection
  }),
  
  actions: {
    // Get the current prefix for a page (dynamic)
    getInitialPrefix(pageId) {
      // Check if we have a stored prefix for this page
      const storedPrefix = this.prefixStorage[pageId]
      
      if (storedPrefix !== undefined) {
        return storedPrefix
      }
      
      // Default rules based on page ID
      if (pageId === 'notemate') {
        return 'notemate'
      } else {
        return '' // Root level for all other pages
      }
    },
    
    // Set/update the prefix for a page
    setPrefix(pageId, prefix) {
      this.prefixStorage[pageId] = prefix
    },
    
    // Add a message to the output for a specific instance
    addOutput(message, pageId = 'default') {
      // Initialize array for this pageId if it doesn't exist
      if (!this.outputMessages[pageId]) {
        this.outputMessages[pageId] = []
      }
      
      this.outputMessages[pageId].push({
        id: Date.now() + Math.random(),
        text: message,
        timestamp: new Date()
      })
    },
    
    // Clear output messages for a specific instance
    clearOutput(pageId = 'default') {
      if (this.outputMessages[pageId]) {
        this.outputMessages[pageId] = []
      }
    },
    
    // Get output messages for a specific instance
    getOutputMessages(pageId = 'default') {
      return this.outputMessages[pageId] || []
    }
  }
})