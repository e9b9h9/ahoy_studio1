# Ebook Reader Implementation Plan

## ✅ Step 1: Configure OpenAI API in Laravel
- Added OpenAI API key to .env file
- Installed openai-php/laravel package
- Published OpenAI config

## ✅ Step 2: Create Database Schema
- Created migrations for ebook feature tables:
  - `ahoy_ebook_library` - Store book information
  - `ahoy_ebook_sections` - Store content sections (chapters/pages/segments)
  - `ahoy_ebook_audio_cache` - Cache generated audio files
  - `ahoy_ebook_reading_sessions` - Track user progress
- Updated schema to support universal content types (not just chapters)
- Added section_type field for flexibility

## ✅ Step 3: Create Laravel Models & Service Classes

### Models Created:
- [x] `EbookLibrary` model with relationships
- [x] `EbookSection` model with content management
- [x] `EbookReadingSession` model with user relationship
- [x] `EbookAudioCache` model for cache management

### Service Classes:
- [ ] **OpenAITTSService**
  - Handle OpenAI TTS API calls
  - Manage voice selection (alloy, echo, fable, onyx, nova, shimmer)
  - Handle rate limiting and errors
  
- [ ] **TextProcessingService**
  - Split text into 4096 character chunks for API limits
  - Maintain sentence boundaries when splitting
  - Track character positions for synchronization
  
- [ ] **EbookParserService**
  - Extract text from PDF files
  - Parse EPUB format
  - Handle plain text files
  - Extract metadata (title, author, etc.)
  
- [ ] **AudioCacheService**
  - Store generated audio files in storage/app/ebook-audio/
  - Track cache usage and cleanup old files
  - Serve cached audio efficiently
  
- [ ] **ReadingProgressService**
  - Save reading position per user
  - Calculate progress percentage
  - Handle bookmarks

## ✅ Step 4: API Controllers & Routes (Partially Complete)

### Controllers:
- [x] **EbookController**
  - Upload and process ebooks ✅
  - List user's library ✅
  - Delete ebooks (pending)
  
- [ ] **AudioGenerationController**
  - Queue audio generation for sections
  - Check generation status
  - Stream audio files
  
- [ ] **ReadingSessionController**
  - Save/retrieve reading progress
  - Update user preferences (voice, speed)
  - Manage bookmarks

### API Routes:
```
POST   /api/ebooks/upload
GET    /api/ebooks
DELETE /api/ebooks/{id}
GET    /api/ebooks/{id}/sections
POST   /api/ebooks/{id}/sections/{section}/generate-audio
GET    /api/ebooks/{id}/sections/{section}/audio
POST   /api/reading-sessions/{ebook}/progress
GET    /api/reading-sessions/{ebook}
PUT    /api/reading-sessions/{ebook}/preferences
```

## ✅ Step 5: Frontend Vue Components (Partially Complete)

### Components Structure:
```
resources/js/features/ebook/
├── EbookLibrary.vue         # Book selection grid/list (integrated in main page)
├── EbookUploader.vue        # Drag-drop upload interface ✅
├── EbookReader.vue          # Main reader container (basic version)
├── components/
│   ├── AudioPlayer.vue      # Custom audio controls
│   ├── TextDisplay.vue      # Synchronized text view
│   ├── SectionNavigator.vue # Section/chapter navigation
│   ├── ReadingSettings.vue  # Voice/speed preferences
│   └── ProgressBar.vue      # Visual reading progress
└── composables/
    ├── useTextSync.js       # Text-audio synchronization
    ├── useAudioPlayer.js    # Audio playback logic
    └── useReadingSession.js # Progress tracking
```

### Key Features:
- [x] Drag-and-drop file upload with progress indicator ✅
- [x] Grid view of uploaded books with covers ✅
- [ ] Custom audio player with speed control (0.5x - 2x)
- [ ] Text highlighting that follows audio
- [ ] Auto-scroll to keep current text visible
- [ ] Section/chapter dropdown navigation
- [ ] Reading progress bar
- [ ] Dark/light mode support

## Step 6: Queue & Background Processing

### Queue Jobs:
- [ ] **ProcessEbookJob**
  - Parse uploaded file
  - Extract sections/chapters
  - Generate metadata
  
- [ ] **GenerateAudioJob**
  - Call OpenAI TTS API
  - Store audio file
  - Update database with file info
  
- [ ] **AudioCacheCleanupJob**
  - Remove old unused audio files
  - Run daily via Laravel scheduler

## Step 7: Pinia Store for State Management

### Store Structure:
```javascript
stores/ebook.js
- currentBook
- currentSection
- playbackState (playing/paused)
- playbackSpeed
- currentPosition
- userPreferences
- audioUrl
- isGenerating
```

## Step 8: Advanced Features (Future)

### Planned Enhancements:
- [ ] Offline mode with downloaded audio
- [ ] Multiple language support
- [ ] Note-taking and annotations
- [ ] Social features (share quotes)
- [ ] Reading statistics and goals
- [ ] Mobile app with React Native
- [ ] Browser extension for web articles

## Technical Considerations

### Performance:
- Chunk size: 4096 characters max per API call
- Pre-generate next section while reading current
- Use CDN for audio file delivery (optional)
- Implement Redis caching for frequently accessed books

### Cost Management:
- OpenAI TTS pricing: ~$15 per 1M characters
- Average book: 80,000 words ≈ 400,000 characters ≈ $6
- Implement usage tracking per user
- Consider subscription model for heavy users

### Error Handling:
- Fallback to browser TTS if API fails
- Retry logic for failed API calls
- User notification for generation progress
- Graceful handling of unsupported file formats

## ✅ CURRENT STATUS: File Upload Working!

### What's Complete:
- [x] Database schema and migrations
- [x] All Eloquent models with relationships
- [x] File upload controller with TXT processing
- [x] Drag-and-drop upload interface
- [x] Library view showing uploaded books
- [x] Basic file validation and processing

### What Works Now:
- Users can upload TXT, PDF, and EPUB files
- TXT files are automatically split into sections
- Files are stored and displayed in library
- Basic metadata extraction (title, author, word count)

## NEXT STEPS:

### Immediate Next Steps (Priority 1):
1. **Basic Text Reading Interface** (EASIEST FIRST STEP)
   - Create individual book reading page `/ebook-reader/{id}/read`
   - Display one section at a time with clean typography
   - Add Previous/Next section navigation
   - Show progress (Section X of Y)
   - Optional: Section dropdown for quick jumping
   - Update reading progress in database

2. **Create OpenAI TTS Service**
   - Build service class to call OpenAI API
   - Test with simple text-to-speech generation
   - Handle API errors and rate limits

3. **Audio Generation**
   - Add "Generate Audio" button to reading interface
   - Create background job for TTS processing
   - Store generated audio files

### Technical Implementation for Text Reader:
**Backend:**
- New route: `GET /ebook-reader/{id}/read/{section?}`
- Controller method: `EbookController@read()`
- Load book with sections and user's reading session
- Handle section navigation and progress updates

**Frontend:**
- New Vue component: `EbookTextReader.vue`
- Clean typography with good line spacing
- Responsive design for different screen sizes
- Progress indicator and section navigation
- Auto-save reading position

**Database:**
- Update `EbookReadingSession` when user changes sections
- Track reading time and last position
- Calculate overall progress percentage

### Medium Priority (Priority 2):
4. **Audio Playback**
   - Create custom audio player component
   - Add play/pause/speed controls
   - Stream cached audio files

5. **Text-Audio Synchronization**
   - Highlight text as audio plays
   - Click text to jump to audio position
   - Auto-scroll with reading

### Later Features (Priority 3):
6. **Enhanced Processing**
   - Add proper PDF text extraction
   - Add EPUB parsing
   - Better section detection

## File Type Processing Considerations

### Current Status:
- **TXT Files**: ✅ Fully implemented with smart section splitting
- **PDF Files**: 📝 Placeholder (needs PDF parsing library)
- **EPUB Files**: 📝 Placeholder (needs EPUB parsing library)

### TXT File Processing (Currently Working):
**How it works:**
- Splits content by double line breaks (`\n\s*\n`)
- Creates sections automatically based on natural paragraph breaks
- Calculates word and character counts
- Works great for plain text books, articles, and documents

**Pros:**
- ✅ Simple and reliable
- ✅ Fast processing
- ✅ No external dependencies
- ✅ Works with any plain text content

**Cons:**
- ❌ No formatting preservation (bold, italic, etc.)
- ❌ No chapter detection
- ❌ Basic section splitting logic

### PDF File Processing (Future Implementation):

**Technical Challenges:**
- **Text Extraction**: PDFs can contain text as images, vectors, or embedded fonts
- **Layout Complexity**: Multi-column layouts, headers, footers, page numbers
- **Formatting Loss**: Bold, italic, font sizes are often lost during extraction
- **Section Detection**: No clear chapter/section boundaries in raw text

**Recommended Libraries:**
```php
// Option 1: smalot/pdfparser (Pure PHP)
composer require smalot/pdfparser

// Option 2: Laravel PDF packages
composer require barryvdh/laravel-dompdf
composer require spatie/pdf-to-text  // Requires poppler-utils
```

**Implementation Strategy:**
1. **Extract raw text** from PDF pages
2. **Clean up artifacts** (page numbers, headers, footers)
3. **Detect sections** using:
   - Font size changes (larger text = headings)
   - Page breaks
   - Chapter numbering patterns
   - Whitespace patterns

**Processing Example:**
```php
private function processPdfFile(EbookLibrary $ebook, string $filePath)
{
    // Using smalot/pdfparser
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($filePath);
    
    $pages = $pdf->getPages();
    $sectionNumber = 1;
    
    foreach ($pages as $page) {
        $text = $page->getText();
        $cleanText = $this->cleanPdfText($text);
        
        if (!empty(trim($cleanText))) {
            EbookSection::create([
                'ebook_id' => $ebook->id,
                'section_number' => $sectionNumber,
                'section_type' => 'page',
                'title' => "Page {$sectionNumber}",
                'content' => $cleanText,
                // ... word counts
            ]);
            $sectionNumber++;
        }
    }
}
```

**PDF Challenges:**
- 🔴 **Quality varies greatly** - Some PDFs extract perfectly, others are garbled
- 🔴 **Large file sizes** - PDFs can be 50MB+ with images
- 🔴 **Processing time** - Text extraction can be slow
- 🔴 **Dependencies** - May require system-level PDF tools

### EPUB File Processing (Future Implementation):

**Technical Overview:**
- **EPUB Format**: ZIP archive containing XHTML, CSS, and metadata
- **Structure**: Standardized with manifest, spine, and content files
- **Advantages**: Designed specifically for ebooks

**Recommended Libraries:**
```php
// Option 1: PHP EPUB Meta
composer require sbrajesh/php-epub-meta

// Option 2: Custom ZIP parsing
// EPUBs are ZIP files with specific structure
```

**EPUB Structure:**
```
book.epub (ZIP file)
├── META-INF/
│   └── container.xml
├── OEBPS/
│   ├── content.opf (manifest)
│   ├── toc.ncx (table of contents)
│   ├── chapter1.xhtml
│   ├── chapter2.xhtml
│   └── styles.css
└── mimetype
```

**Implementation Strategy:**
1. **Extract ZIP** to temporary directory
2. **Parse content.opf** to get reading order
3. **Extract metadata** (title, author, description)
4. **Process each chapter** as separate section
5. **Clean XHTML** to plain text
6. **Preserve chapter structure**

**Processing Example:**
```php
private function processEpubFile(EbookLibrary $ebook, string $filePath)
{
    $zip = new ZipArchive();
    $zip->open($filePath);
    
    // Extract to temp directory
    $tempDir = storage_path('temp/epub_' . time());
    $zip->extractTo($tempDir);
    
    // Parse content.opf for reading order
    $contentOpf = $this->parseContentOpf($tempDir);
    
    $sectionNumber = 1;
    foreach ($contentOpf->spine as $item) {
        $xhtmlPath = $tempDir . '/OEBPS/' . $item->href;
        $content = $this->extractTextFromXhtml($xhtmlPath);
        
        EbookSection::create([
            'ebook_id' => $ebook->id,
            'section_number' => $sectionNumber,
            'section_type' => 'chapter',
            'title' => $item->title ?? "Chapter {$sectionNumber}",
            'content' => $content,
            // ... word counts
        ]);
        $sectionNumber++;
    }
    
    // Cleanup temp directory
    File::deleteDirectory($tempDir);
}
```

**EPUB Advantages:**
- ✅ **Structured content** with proper chapters
- ✅ **Rich metadata** (title, author, description, cover)
- ✅ **Table of contents** with chapter titles
- ✅ **Standardized format** - predictable structure

**EPUB Challenges:**
- 🔴 **HTML complexity** - Need to strip HTML tags cleanly
- 🔴 **File extraction** - Temporary file management
- 🔴 **Varied implementations** - Not all EPUBs follow standards perfectly

### Implementation Priority:

**Phase 1 (Current):** ✅ TXT files working perfectly

**Phase 2 (Next):** 📊 EPUB processing
- More predictable structure
- Better user experience (proper chapters)
- Rich metadata extraction

**Phase 3 (Later):** 📄 PDF processing  
- More complex and unpredictable
- Quality varies significantly
- May need manual cleanup options

### Development Approach:

**For EPUB Implementation:**
1. Add `composer require sbrajesh/php-epub-meta`
2. Create `EpubParserService` class
3. Extract chapters with proper titles
4. Preserve table of contents structure
5. Add cover image extraction

**For PDF Implementation:**
1. Add `composer require smalot/pdfparser`
2. Create `PdfParserService` class  
3. Implement text cleaning algorithms
4. Add page-by-page or section detection
5. Handle OCR for image-based PDFs (advanced)

### User Experience Considerations:

**File Upload Feedback:**
- Show processing progress for large files
- Display file type-specific warnings
- Provide quality indicators after processing

**Processing Options:**
- Allow users to choose section detection method
- Provide preview of detected sections
- Enable manual section editing for PDFs

**Fallback Strategies:**
- If automatic processing fails, allow manual text paste
- Provide "Upload failed" options
- Clear error messages for unsupported files

## Development Order:

1. **✅ Backend First** (DONE):
   - Models and relationships ✅
   - Basic file upload and text extraction ✅
   - OpenAI TTS service (NEXT STEP)
   
2. **✅ Basic Frontend** (DONE):
   - Simple upload form ✅
   - Text display from database ✅
   - Basic audio playback (NEXT STEP)
   
3. **Integration**:
   - Connect TTS generation
   - Implement caching
   - Add progress tracking
   
4. **Polish**:
   - Text-audio sync
   - Advanced UI features
   - Performance optimization

## Testing Strategy:

- Unit tests for service classes
- Feature tests for API endpoints
- Frontend component tests
- E2E tests for complete flow
- Load testing for audio streaming