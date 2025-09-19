# Shopbuddy
Terminal Like interface for doing things in my app (http requests)

# Code Change Tracker (Notemate)

## Project Summary
A development tool that automatically associates code changes with specific features or tasks, by automatically adding comments to files.

## App Names
App Name: Notemate
Terminal: Shopbuddy

## Files
resources\js\pages\Notemate.vue: main page 
resources\js\features\notemate\NotemateLayout.vue: layout

## Folders
resources\js\features\notemate: all Notemate features
resources\js\features\Shopbuddy: all Shopbuddy features

### Goals
Start Feature Tracking - Developer declares they're working on a specific feature
Automatic Change Detection - System monitors files for modifications during development
Code Annotation - System automatically adds inline comments linking code to features
Feature Navigation - Browse by feature to see all related code
Real-time file monitoring during development
Intelligent detection of new vs existing code
Automatic comment injection for code annotation
Database tracking of features and file relationships

## Workflow 
Start file watcher > start working on a feature "example feature"> view/see files that have changed/files and folders added on left sidebar > open files that have changed > add comment that is the feature "example feature" or change comment to something else > save file and inject comments into project folder > stop working on the feature > restart the file watcher from the new time > view features right sidebar 

## Tech Stack
Main Language (backend): Php, Laravel, Database: MySQL, Front End: Vue, Build tool: vite 

## Core Components

### File System Watcher
WebSocket Server

## File Watcher Requirements     
1. Real-time Detection - Monitor file changes as they happen during development
2. Change Types - New files created, Existing files modified, Files deleted, Folder structure, changes
3. WebSocket Communication - Push changes to frontend immediately
4. State Management - Track: Which feature is currently active, Which files changed during that feature's
  development, Timestamp of changes      
5. Selective Monitoring - Ignore certain directories (node_modules, vendor, .git, etc.)

## File Watcher Tech Stack
Node.js with chokidar
  
### Commenting 
Inject comments on files 
- Add feature-name comments to specific lines of code
- Handle different comment styles for various file types (.php, .js, .vue, .css, .blade.php)
- Read/modify/write files with injected comments

### Features
- REST API endpoints for starting/stopping feature tracking
- Mark changes endpoint
- Retrieve feature list and individual feature code changes

## Files to work on

### File Watcher
database/migrations/create_file_changes_table.php
app/Models/FileChange.php
app/Services/FileWatcherService.php

### Features 
database/migrations/create_features_table.php
app/Models/Feature.php
app/Http/Controllers/FeatureController.php



# Future development additions
Save copies of features in a features table (searchable accross projects)
   - notemate_codefolder_feature_contents: id, feature (foreign key), codeline, comment, file_name  
   - notemate_projects_codefolders: notemate_project_id (foriegn key (nullable)), codefolder path
   - notemate_projects: project name

## Database Setup

## Phase 3: Laravel Backend
4. Create API endpoints
   - POST /api/features/start — Start tracking a new feature
   - POST /api/features/stop — Stop current feature tracking
   - GET /api/features — List all features

5. WebSocket broadcasting
   - Configure Laravel Reverb 
   - Broadcast file changes to frontend
   - Set up event listeners

## Phase 4: Vue Frontend
6. File changes sidebar (left)
   - Real-time list of changed files
   - Show change type (new/modified/deleted)
   - Click to view/edit feature comment

7. Features sidebar (right)
   - List of all tracked features
   - Click to see associated file changes
   - Filter and search capabilities

8. Feature tracking controls
   - Start/stop feature button
   - Feature name input
   - Current status indicator

9. Add/Edit Comments Section
    - Middle section have table with two columns codeline and comment 
    - Comment will show the current feature as the comment if there isn't already a comment
    - Editable to change the comment 
    - Send/inject: adds the comments to the actual file 

## Phase 5: Comment Injection
9. Comment injection service
   - Parse different file types (.php, .js, .vue, .css)
   - Inject comments at specified lines
   - Handle comment syntax for each file type
   - Preserve existing code formatting
