import chokidar from 'chokidar';
import axios from 'axios';
import path from 'path';

class FileWatcher {
    constructor() {
        this.watcher = null;
        this.activeFolder = null;
        this.baseUrl = 'http://ahoy_studio1.test'; // Update this to your Laravel app URL
        this.checkInterval = 5000; // Check for active folder changes every 5 seconds
    }

    async start() {
        console.log('🚀 File Watcher starting...');
        
        // Check for active folder and start watching
        await this.checkAndWatch();
        
        // Set interval to check for active folder changes
        setInterval(() => {
            this.checkAndWatch();
        }, this.checkInterval);
    }

    async checkAndWatch() {
        try {
            const response = await axios.get(`${this.baseUrl}/api/notemate/codefolders`);
            const folders = response.data;
            const activeFolder = folders.find(folder => folder.is_working);

            if (!activeFolder) {
                if (this.watcher) {
                    console.log('❌ No active folder found, stopping watcher');
                    this.stopWatcher();
                }
                return;
            }

            // If active folder changed, restart watcher
            if (!this.activeFolder || this.activeFolder.id !== activeFolder.id) {
                console.log(`📁 Active folder changed: ${activeFolder.path}`);
                this.startWatcher(activeFolder);
            }

        } catch (error) {
            console.error('❌ Error checking active folder:', error.message);
        }
    }

    startWatcher(folder) {
        // Stop existing watcher if any
        this.stopWatcher();

        this.activeFolder = folder;
        const watchPath = folder.path;

        console.log(`👀 Starting to watch: ${watchPath}`);

        
				this.watcher = chokidar.watch(watchPath, {
					ignored: (path, stats) => {
							// Normalize path separators to forward slashes
							const normalizedPath = path.replace(/\\/g, '/');
							
							// Debug: log what's being checked
							// console.log(`Checking: ${normalizedPath}`);
							
							const ignorePatterns = [
									// Directory patterns
									'/node_modules/',
									'/vendor/',
									'/.git/',
									'/storage/framework/',
									'/storage/cache/',
									'/public/storage/',
									'/public/hot/',
									'/resources/js/actions/',
									'/resources/js/routes/',
									'/.vscode/',
									'/.idea/',
									'/dist/',
									'/build/',
									'/coverage/',
									'/.nuxt/',
									'/.next/',
									'/bootstrap/cache/',
									
									// File patterns
									'.DS_Store',
									'Thumbs.db',
									'.phpunit.result.cache',
									'composer.lock',
									'package-lock.json',
									'yarn.lock',
							];
							
							// Check if path contains any ignore patterns
							const shouldIgnore = ignorePatterns.some(pattern => {
									if (pattern.startsWith('/') && pattern.endsWith('/')) {
											// Directory pattern
											return normalizedPath.includes(pattern);
									} else {
											// File pattern
											return normalizedPath.includes(pattern) || normalizedPath.endsWith(pattern);
									}
							});
							
							// Also ignore hidden files/folders
							const isHidden = normalizedPath.split('/').some(part => part.startsWith('.') && part !== '.');
							
							// Also ignore temp files
							const isTempFile = /\.(swp|swo|bak|orig|rej|tmp|temp)$/.test(normalizedPath) || normalizedPath.endsWith('~');
							
							if (shouldIgnore || isHidden || isTempFile) {
									console.log(`🚫 Ignoring: ${normalizedPath}`);
									return true;
							}
							
							return false;
					},
					ignoreInitial: true,
					persistent: true,
					depth: 10,
					awaitWriteFinish: {
							stabilityThreshold: 100,
							pollInterval: 50
					}
			});
        // Set up event listeners
        this.watcher
            .on('add', (filePath) => this.handleChange('new', filePath))
            .on('change', (filePath) => this.handleChange('modified', filePath))
            .on('unlink', (filePath) => this.handleChange('deleted', filePath))
            .on('error', (error) => console.error('❌ Watcher error:', error))
            .on('ready', () => console.log('✅ Initial scan complete. Ready for changes.'));
    }

    stopWatcher() {
        if (this.watcher) {
            this.watcher.close();
            this.watcher = null;
            this.activeFolder = null;
        }
    }

    async handleChange(type, filePath) {
        if (!this.activeFolder) return;

        // Get relative path from the watched folder
        const relativePath = path.relative(this.activeFolder.path, filePath);
        
        console.log(`📝 ${type.toUpperCase()}: ${relativePath}`);

        try {
            // Log file change
            await axios.post(`${this.baseUrl}/api/notemate/file-changes`, {
                codefolder_id: this.activeFolder.id,
                file_path: relativePath,
                change_type: type,
                detected_at: new Date().toISOString()
            });

            // Process codelines for modified files to alert module imports
            if (type === 'modified' || type === 'new') {
                try {
                    await axios.post(`${this.baseUrl}/notemate/codelines/process`, {
                        file_path: filePath
                    });
                } catch (codelineError) {
                    console.error('❌ Error processing codelines:', codelineError.message);
                }
            }
        } catch (error) {
            console.error('❌ Error sending change to server:', error.message);
        }
    }
}

// Start the watcher
const watcher = new FileWatcher();
watcher.start();

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n⏹️  Shutting down file watcher...');
    watcher.stopWatcher();
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n⏹️  Shutting down file watcher...');
    watcher.stopWatcher();
    process.exit(0);
});