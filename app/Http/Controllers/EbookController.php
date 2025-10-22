<?php

namespace App\Http\Controllers;

use App\Models\EbookLibrary;
use App\Models\EbookSection;
use App\Models\EbookReadingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EbookController extends Controller
{
    /**
     * Display the ebook library.
     */
    public function index()
    {
        $ebooks = EbookLibrary::with('currentUserSession')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('EbookReader', [
            'ebooks' => $ebooks,
        ]);
    }

    /**
     * Upload and store a new ebook.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,pdf,epub|max:50000', // 50MB max
            'title' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $fileExtension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        
        // Ensure ebooks directory exists
        if (!Storage::disk('local')->exists('ebooks')) {
            Storage::disk('local')->makeDirectory('ebooks');
        }
        
        // Generate unique filename
        $fileName = time() . '_' . str_replace(' ', '_', $originalName);
        $filePath = $file->storeAs('ebooks', $fileName, 'local');

        // Extract title from filename if not provided
        $title = $request->input('title') ?: pathinfo($originalName, PATHINFO_FILENAME);
        
        // Create ebook record
        $ebook = EbookLibrary::create([
            'title' => $title,
            'author' => $request->input('author', 'Unknown'),
            'description' => null,
            'cover_image' => null,
            'file_path' => $filePath,
            'file_type' => $fileExtension,
            'total_sections' => 0,
            'total_words' => 0,
            'metadata' => [
                'original_name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ],
        ]);

        try {
            // Process the file content based on type
            $this->processEbookContent($ebook, $filePath, $fileExtension);

            return redirect()->route('ebook.reader')
                ->with('success', 'Ebook uploaded and processed successfully!');
        } catch (\Exception $e) {
            // If processing fails, delete the ebook record and file
            $ebook->delete();
            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
            
            return redirect()->route('ebook.reader')
                ->with('error', 'Failed to process ebook: ' . $e->getMessage());
        }
    }

    /**
     * Process ebook content and create sections.
     */
    private function processEbookContent(EbookLibrary $ebook, string $filePath, string $fileType)
    {
        $fullPath = storage_path('app/' . $filePath);
        
        switch ($fileType) {
            case 'txt':
                $this->processTxtFile($ebook, $fullPath);
                break;
            case 'pdf':
                // For now, we'll handle PDF as a single section
                // Later we can add PDF parsing library
                $this->processPdfFile($ebook, $fullPath);
                break;
            case 'epub':
                // For now, we'll handle EPUB as a single section
                // Later we can add EPUB parsing library
                $this->processEpubFile($ebook, $fullPath);
                break;
        }
    }

    /**
     * Process plain text file.
     */
    private function processTxtFile(EbookLibrary $ebook, string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found at path: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \Exception("Failed to read file content from: {$filePath}");
        }
        
        if (empty(trim($content))) {
            throw new \Exception("File is empty or contains only whitespace");
        }
        
        // Split by double line breaks for simple sectioning
        $sections = preg_split('/\n\s*\n/', $content, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($sections)) {
            $sections = [$content];
        }

        $totalWords = 0;
        $sectionNumber = 1;

        foreach ($sections as $sectionContent) {
            $sectionContent = trim($sectionContent);
            if (empty($sectionContent)) {
                continue;
            }

            $wordCount = str_word_count($sectionContent);
            $characterCount = strlen($sectionContent);
            $totalWords += $wordCount;

            EbookSection::create([
                'ebook_id' => $ebook->id,
                'section_number' => $sectionNumber,
                'section_type' => 'section',
                'title' => null,
                'content' => $sectionContent,
                'word_count' => $wordCount,
                'character_count' => $characterCount,
                'audio_generated' => false,
            ]);

            $sectionNumber++;
        }

        // Check if any sections were created
        $sectionsCreated = $sectionNumber - 1;
        if ($sectionsCreated === 0) {
            throw new \Exception("No valid sections could be created from file content");
        }
        
        // Update ebook totals
        $ebook->update([
            'total_sections' => $sectionsCreated,
            'total_words' => $totalWords,
        ]);
    }

    /**
     * Process PDF file (placeholder).
     */
    private function processPdfFile(EbookLibrary $ebook, string $filePath)
    {
        // For now, create a single section with a placeholder
        // In production, you'd use a PDF parsing library like smalot/pdfparser
        EbookSection::create([
            'ebook_id' => $ebook->id,
            'section_number' => 1,
            'section_type' => 'page',
            'title' => 'PDF Content',
            'content' => 'PDF processing will be implemented. File uploaded successfully.',
            'word_count' => 10,
            'character_count' => 60,
            'audio_generated' => false,
        ]);

        $ebook->update([
            'total_sections' => 1,
            'total_words' => 10,
        ]);
    }

    /**
     * Process EPUB file (placeholder).
     */
    private function processEpubFile(EbookLibrary $ebook, string $filePath)
    {
        // For now, create a single section with a placeholder
        // In production, you'd use an EPUB parsing library
        EbookSection::create([
            'ebook_id' => $ebook->id,
            'section_number' => 1,
            'section_type' => 'chapter',
            'title' => 'EPUB Content',
            'content' => 'EPUB processing will be implemented. File uploaded successfully.',
            'word_count' => 10,
            'character_count' => 65,
            'audio_generated' => false,
        ]);

        $ebook->update([
            'total_sections' => 1,
            'total_words' => 10,
        ]);
    }

    /**
     * Display the reading interface for an ebook.
     */
    public function read(EbookLibrary $ebook, $section = null)
    {
        // Get or create reading session for current user
        $readingSession = EbookReadingSession::getOrCreateForUser(
            auth()->id(),
            $ebook->id
        );

        // Determine which section to show
        $sectionNumber = $section ?? $readingSession->current_section ?? 1;
        
        // Get the current section
        $currentSection = EbookSection::where('ebook_id', $ebook->id)
            ->where('section_number', $sectionNumber)
            ->first();
            
        if (!$currentSection) {
            // Check if the ebook has any sections at all
            $firstSection = EbookSection::where('ebook_id', $ebook->id)
                ->orderBy('section_number')
                ->first();
                
            if (!$firstSection) {
                // No sections exist - ebook processing may have failed
                return redirect()->route('ebook.reader')
                    ->with('error', 'This ebook has no readable content. Please try uploading again.');
            }
            
            // If we're not already trying section 1, redirect to first available section
            if ($sectionNumber != $firstSection->section_number) {
                return redirect()->route('ebook.read', [
                    'ebook' => $ebook->id, 
                    'section' => $firstSection->section_number
                ]);
            }
            
            // This should never happen, but prevent infinite loop
            return redirect()->route('ebook.reader')
                ->with('error', 'Unable to load ebook content.');
        }

        // Get all sections for navigation
        $sections = EbookSection::where('ebook_id', $ebook->id)
            ->orderBy('section_number')
            ->select(['id', 'section_number', 'title', 'section_type'])
            ->get();

        // Update reading session if section changed
        if ($readingSession->current_section !== $sectionNumber) {
            $readingSession->updateProgress($sectionNumber, 0);
        }

        // Get navigation info
        $previousSection = $sectionNumber > 1 ? $sectionNumber - 1 : null;
        $nextSection = $sectionNumber < $ebook->total_sections ? $sectionNumber + 1 : null;

        return Inertia::render('EbookTextReader', [
            'ebook' => $ebook,
            'currentSection' => $currentSection,
            'sections' => $sections,
            'readingSession' => $readingSession,
            'navigation' => [
                'current' => $sectionNumber,
                'total' => $ebook->total_sections,
                'previous' => $previousSection,
                'next' => $nextSection,
            ],
        ]);
    }
}