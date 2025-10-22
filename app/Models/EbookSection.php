<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EbookSection extends Model
{
    use HasFactory;

    protected $table = 'ahoy_ebook_sections';

    protected $fillable = [
        'ebook_id',
        'section_number',
        'section_type',
        'title',
        'content',
        'word_count',
        'character_count',
        'audio_generated',
    ];

    protected $casts = [
        'section_number' => 'integer',
        'word_count' => 'integer',
        'character_count' => 'integer',
        'audio_generated' => 'boolean',
    ];

    /**
     * Section types available.
     */
    const SECTION_TYPES = [
        'chapter' => 'Chapter',
        'page' => 'Page',
        'section' => 'Section',
        'segment' => 'Segment',
        'article' => 'Article',
        'paragraph' => 'Paragraph',
    ];

    /**
     * Get the ebook that owns the section.
     */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(EbookLibrary::class, 'ebook_id');
    }

    /**
     * Get the audio cache entries for this section.
     */
    public function audioCache(): HasMany
    {
        return $this->hasMany(EbookAudioCache::class, 'section_number', 'section_number')
            ->where('ebook_id', $this->ebook_id);
    }

    /**
     * Get the display name for the section type.
     */
    public function getSectionTypeDisplayAttribute(): string
    {
        return self::SECTION_TYPES[$this->section_type] ?? 'Section';
    }

    /**
     * Get the formatted title with section type and number.
     */
    public function getFormattedTitleAttribute(): string
    {
        $typeDisplay = $this->section_type_display;
        $number = $this->section_number;
        
        if ($this->title) {
            return "{$typeDisplay} {$number}: {$this->title}";
        }
        
        return "{$typeDisplay} {$number}";
    }

    /**
     * Calculate reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        // Average reading speed: 200 words per minute
        return ceil($this->word_count / 200);
    }

    /**
     * Get the chunks needed for TTS generation.
     */
    public function getTextChunks(int $maxChunkSize = 4096): array
    {
        $chunks = [];
        $text = $this->content;
        
        // If text fits in one chunk, return as is
        if (strlen($text) <= $maxChunkSize) {
            return [$text];
        }
        
        // Split by sentences to avoid breaking in the middle of words
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $currentChunk = '';
        
        foreach ($sentences as $sentence) {
            // If a single sentence is too long, we need to split it
            if (strlen($sentence) > $maxChunkSize) {
                if ($currentChunk) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }
                
                // Split long sentence by words
                $words = explode(' ', $sentence);
                $tempChunk = '';
                
                foreach ($words as $word) {
                    if (strlen($tempChunk . ' ' . $word) > $maxChunkSize) {
                        $chunks[] = trim($tempChunk);
                        $tempChunk = $word;
                    } else {
                        $tempChunk = $tempChunk ? $tempChunk . ' ' . $word : $word;
                    }
                }
                
                if ($tempChunk) {
                    $currentChunk = $tempChunk;
                }
            } elseif (strlen($currentChunk . ' ' . $sentence) > $maxChunkSize) {
                // Current chunk is full, start a new one
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                // Add sentence to current chunk
                $currentChunk = $currentChunk ? $currentChunk . ' ' . $sentence : $sentence;
            }
        }
        
        // Add any remaining text
        if ($currentChunk) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }

    /**
     * Check if audio is fully generated for this section.
     */
    public function isAudioComplete(): bool
    {
        if (!$this->audio_generated) {
            return false;
        }

        $expectedChunks = count($this->getTextChunks());
        $generatedChunks = $this->audioCache()->count();
        
        return $generatedChunks >= $expectedChunks;
    }
}