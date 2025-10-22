# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## User Preferences DO NOT DELETE 
- Always review your plan before you make changes to code
- Wait for me to approve the plan before making changes to code files 
- Ask questions to clarify 
- Do not jump ahead or add extra things unless you ask 
- Use javascript over typescript where possible
- Always review code changes by file name when complete 

## Styling preferences
- Simple/Minimal and condensed UI

## Environment 
- Running in WSL terminal on Windows computer - cannot execute direct commands
- DO NOT use PHP artisan commands through WSL environment

## Development Commands

**Available npm scripts:**
```bash
npm run dev          # Start Vite dev server
npm run build        # Build for production
npm run build:ssr    # Build with SSR support
npm run lint         # Run ESLint with auto-fix
npm run format       # Format code with Prettier
npm run format:check # Check code formatting
npm run file-watcher # Start the file monitoring service
```

**Composer scripts (for full-stack development):**
```bash
composer dev         # Concurrent Laravel server + queue + Vite
composer dev:ssr     # Full SSR development with logs
composer test        # Run PestPHP test suite
```

**Important notes about commands:**
- DO NOT use PHP artisan commands through WSL environment
- The environment is running in WSL terminal on Windows - cannot execute direct commands
- For development, use `npm run dev` for Vite frontend server

**Code Quality Tools:**
- ESLint configuration with Vue + TypeScript rules and Prettier integration
- Laravel Pint for PHP code formatting
- Run `npm run lint` before committing changes
- Run `npm run format` to auto-format code

**Testing:**
- Backend: PestPHP testing framework configured
- Test files located in `tests/` directory
- Run tests: `composer test` (preferred in WSL)
- Test database: SQLite in-memory for fast testing
- No frontend testing framework currently configured

## Project Overview

Laravel + Vue.js + Inertia SPA application with three main features:
1. **Notemate** - Code change tracker that associates code changes with features/tasks
2. **Shopbuddy** - Terminal-like interface with command registry system for app interactions
3. **Ebook Reader** - Text-to-speech ebook reader with OpenAI TTS integration

### Tech Stack
- **Backend**: PHP 8.2, Laravel 12, MySQL (SQLite for development)
- **Frontend**: Vue 3 with Composition API, TypeScript strict mode
- **SPA Layer**: Inertia.js 2.1 with SSR support (port 13714)
- **State Management**: Pinia 3.0 stores
- **UI Framework**: Reka UI 2.4 components, Tailwind CSS v4, Lucide icons
- **Build System**: Vite 7.0 with Laravel Vite plugin
- **Authentication**: Laravel Fortify with two-factor authentication
- **Routing**: Laravel Wayfinder generates TypeScript route definitions
- **Development Database**: SQLite (database.sqlite)
- **AI Integration**: OpenAI API for text-to-speech generation

## Architecture

### Inertia.js SPA Pattern
- Server-side routing with client-side rendering (no traditional API layer)
- Controllers return Inertia responses with props instead of JSON
- SSR enabled for better SEO and initial load performance
- Routes automatically typed via Wayfinder

### Authentication System
- Laravel Fortify with comprehensive 2FA support
- Auth pages: Login, Register, Password Reset, 2FA Challenge
- Settings pages: Profile, Password, Two-Factor Authentication
- User model includes two-factor authentication columns

### Frontend Architecture
- **Component Organization**: Feature-based structure with shared UI components
- **Layouts**: Hierarchical layout system (App → Sidebar → Page specific)
- **State Management**: Pinia stores for complex state (e.g., `shopbuddy.store.js`)
- **Type Safety**: Full TypeScript with strict mode, path aliases (`@/*` → `resources/js/*`)
- **Styling**: CSS custom properties for theming, light/dark mode support

### Key Feature Patterns

**Shopbuddy Terminal Interface:**
- Command structure: `sb [prefix] [command] [subcommand]`
- Components: `Shopbuddy.vue`, `ShopbuddyInput.vue`, `ShopbuddyOutput.vue`
- Logic: `useShopbuddy.js` composable handles command parsing and execution
- State: `shopbuddy.store.js` manages command prefixes and terminal state
- Registry: Command registry system in `useShopbuddy.js` for extensible commands

**Notemate Code Tracking:**
- Database: Complex schema with multiple tables for comprehensive code tracking:
  - `notemate_codefolders`: Project paths and working status
  - `notemate_file_changes`: Tracks file modifications
  - `notemate_snippet_features`: Associates code snippets with features
  - `notemate_framefiles`, `notemate_codelines`: Granular code tracking
  - `notemate_languages`, `notemate_extensions`: Language and file type tracking
  - `master_codelines`: Advanced codeline analysis with variables and structure detection
- Layout: `NotemateLayout.vue` with dedicated route at `/notemate`
- File Monitoring: Real-time file watcher service (`file-watcher.js`) using chokidar
- Processing: `CodelineProcessingService.php` with rule-based code analysis
- Architecture: Designed for automatic code change tracking, comment injection, and feature association

**Ebook Reader System:**
- Database: Ebook management with comprehensive features:
  - `ahoy_ebook_library`: Book storage with metadata (title, author, file info)
  - `ahoy_ebook_sections`: Content sections for granular navigation and audio generation
  - `ahoy_ebook_reading_sessions`: User progress tracking and preferences
  - `ahoy_ebook_audio_cache`: Cached TTS-generated audio files
- File Processing: Supports TXT files with automatic section detection (PDF/EPUB planned)
- OpenAI Integration: Text-to-speech generation using OpenAI TTS API
- Components: Upload interface, library view, and reading interface (in development)
- Architecture: Designed for file upload, content processing, audio generation, and synchronized reading

### Component Patterns
- **UI Components**: Located in `resources/js/components/ui/` following shadcn/ui patterns
- **Composition API**: Use `<script setup>` with TypeScript interfaces for props
- **Class Variants**: Utilize class-variance-authority for component variants
- **Composables**: Shared reactive logic in `resources/js/composables/`

### Database Schema
- **Users**: Enhanced with two-factor authentication columns
- **Notemate Tables**: 
  - `notemate_codefolders`: Project paths and working status
  - `notemate_file_changes`, `notemate_snippet_features`: Change tracking
  - `notemate_framefiles`, `notemate_codelines`: Code analysis
  - `master_codelines`: Advanced analysis with variables and structure
  - `notemate_languages`, `notemate_extensions`: File type management
  - `filetrees`, `temp_codelines`, `variables`, `codeblocks`: Additional code analysis
- **Ebook Tables**: 
  - `ahoy_ebook_library`: Book information and metadata
  - `ahoy_ebook_sections`: Content sections and navigation
  - `ahoy_ebook_reading_sessions`: User progress and preferences
  - `ahoy_ebook_audio_cache`: TTS-generated audio file cache
- **Standard Laravel**: Cache, jobs, sessions, and personal access tokens tables

### File Organization
**Backend Structure:**
- `app/Http/Controllers/` - Auth, Settings, and feature controllers with Inertia responses
- `app/Models/` - Eloquent models (User, Notemate*, Ebook*, etc.)
- `app/Services/` - Business logic services (CodelineProcessingService, etc.)
- `app/Services/Rules/` - Modular code analysis rules (VueVariableRule, CommentRule, etc.)
- `routes/` - Organized by feature (web.php, auth.php, settings.php)

**Frontend Structure:**
- `resources/js/features/` - Feature-specific components (notemate/, shopbuddy/, ebook/)
- `resources/js/components/ui/` - Shared UI component library
- `resources/js/layouts/` - Layout components (App, Auth, Settings)
- `resources/js/pages/` - Inertia page components
- `resources/js/composables/` - Shared reactive logic
- `resources/js/routes/` - Generated TypeScript route definitions

## Common Development Tasks

**Database Operations:**
```bash
# Note: These commands should be run outside WSL or using composer scripts
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed  # Fresh database with seeders
php artisan tinker               # Interactive PHP shell
```

**Component Creation Patterns:**
- Vue components: Use `<script setup lang="ts">` with TypeScript
- Props: Define TypeScript interfaces for type safety
- State: Use Pinia stores for shared state, component refs for local state
- Styling: Tailwind classes with CSS custom properties for theming

**Working with Inertia:**
- Page components receive props from Laravel controllers
- Use `router.visit()` for navigation (not axios or fetch)
- Form submissions: Use `useForm` from `@inertiajs/vue3`
- Preserve scroll position: Add `preserveScroll` to forms

**File Monitoring Service:**
- Start watcher: `npm run file-watcher`
- Monitors changes in tracked folders from `notemate_codefolders`
- Automatically posts file changes to Laravel backend
- Processes files through rule-based analysis pipeline
- Ignores node_modules, vendor, .git directories

**OpenAI Integration:**
- Requires OPENAI_API_KEY in .env file
- TTS generation through OpenAI API for ebook audio
- Audio files cached in storage/app/ebook-audio/
- Supports voice selection and speed control