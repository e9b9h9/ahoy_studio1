<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EbookReadingSession extends Model
{
    use HasFactory;

    protected $table = 'ahoy_ebook_reading_sessions';

    protected $fillable = [
        'user_id',
        'ebook_id',
        'current_section',
        'current_position',
        'progress_percentage',
        'voice_preference',
        'speed_preference',
        'auto_scroll',
        'highlight_text',
        'last_read_at',
    ];

    protected $casts = [
        'current_section' => 'integer',
        'current_position' => 'integer',
        'progress_percentage' => 'float',
        'speed_preference' => 'float',
        'auto_scroll' => 'boolean',
        'highlight_text' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    /**
     * Available OpenAI voices.
     */
    const AVAILABLE_VOICES = [
        'alloy' => 'Alloy',
        'echo' => 'Echo',
        'fable' => 'Fable',
        'onyx' => 'Onyx',
        'nova' => 'Nova',
        'shimmer' => 'Shimmer',
    ];

    /**
     * Available playback speeds.
     */
    const SPEED_OPTIONS = [
        0.5,
        0.75,
        1.0,
        1.25,
        1.5,
        1.75,
        2.0,
    ];

    /**
     * Get the user that owns the reading session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ebook being read.
     */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(EbookLibrary::class, 'ebook_id');
    }

    /**
     * Get the current section being read.
     */
    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(EbookSection::class, 'current_section', 'section_number')
            ->where('ebook_id', $this->ebook_id);
    }

    /**
     * Update the reading progress.
     */
    public function updateProgress(int $sectionNumber, int $position): void
    {
        $ebook = $this->ebook;
        $totalSections = $ebook->total_sections;
        
        // Calculate overall progress percentage
        if ($totalSections > 0) {
            $sectionProgress = ($sectionNumber - 1) / $totalSections;
            
            // Get current section to calculate within-section progress
            $section = EbookSection::where('ebook_id', $this->ebook_id)
                ->where('section_number', $sectionNumber)
                ->first();
            
            if ($section && $section->character_count > 0) {
                $withinSectionProgress = $position / $section->character_count;
                $sectionWeight = 1 / $totalSections;
                $progressPercentage = ($sectionProgress + ($withinSectionProgress * $sectionWeight)) * 100;
            } else {
                $progressPercentage = $sectionProgress * 100;
            }
        } else {
            $progressPercentage = 0;
        }

        $this->update([
            'current_section' => $sectionNumber,
            'current_position' => $position,
            'progress_percentage' => min(100, $progressPercentage),
            'last_read_at' => now(),
        ]);
    }

    /**
     * Get formatted voice name.
     */
    public function getVoiceNameAttribute(): string
    {
        return self::AVAILABLE_VOICES[$this->voice_preference] ?? 'Alloy';
    }

    /**
     * Get formatted speed display.
     */
    public function getSpeedDisplayAttribute(): string
    {
        return $this->speed_preference . 'x';
    }

    /**
     * Get the time since last read.
     */
    public function getTimeSinceLastReadAttribute(): string
    {
        if (!$this->last_read_at) {
            return 'Never';
        }

        return $this->last_read_at->diffForHumans();
    }

    /**
     * Check if this is a new reading session (never read).
     */
    public function isNew(): bool
    {
        return $this->current_section === 1 && $this->current_position === 0;
    }

    /**
     * Reset reading progress to beginning.
     */
    public function resetProgress(): void
    {
        $this->update([
            'current_section' => 1,
            'current_position' => 0,
            'progress_percentage' => 0,
        ]);
    }

    /**
     * Get or create a reading session for a user and ebook.
     */
    public static function getOrCreateForUser(int $userId, int $ebookId): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'ebook_id' => $ebookId,
            ],
            [
                'current_section' => 1,
                'current_position' => 0,
                'progress_percentage' => 0,
                'voice_preference' => 'alloy',
                'speed_preference' => 1.0,
                'auto_scroll' => true,
                'highlight_text' => true,
            ]
        );
    }
}