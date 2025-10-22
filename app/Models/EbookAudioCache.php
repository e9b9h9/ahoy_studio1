<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EbookAudioCache extends Model
{
    use HasFactory;

    protected $table = 'ahoy_ebook_audio_cache';

    protected $fillable = [
        'ebook_id',
        'section_number',
        'chunk_number',
        'voice',
        'speed',
        'audio_file_path',
        'duration_seconds',
        'file_size_bytes',
        'text_content',
        'character_start',
        'character_end',
        'last_accessed_at',
    ];

    protected $casts = [
        'section_number' => 'integer',
        'chunk_number' => 'integer',
        'speed' => 'float',
        'duration_seconds' => 'integer',
        'file_size_bytes' => 'integer',
        'character_start' => 'integer',
        'character_end' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the ebook that owns the audio cache.
     */
    public function ebook(): BelongsTo
    {
        return $this->belongsTo(EbookLibrary::class, 'ebook_id');
    }

    /**
     * Get the section this audio belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(EbookSection::class, 'section_number', 'section_number')
            ->where('ebook_id', $this->ebook_id);
    }

    /**
     * Get the full audio file path.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->audio_file_path);
    }

    /**
     * Get the audio file URL for streaming.
     */
    public function getAudioUrlAttribute(): string
    {
        // This will be handled by a controller route for security
        return route('ebook.audio.stream', [
            'ebook' => $this->ebook_id,
            'section' => $this->section_number,
            'chunk' => $this->chunk_number,
        ]);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $size = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return sprintf('%d:%02d:%02d', $hours, $remainingMinutes, $remainingSeconds);
    }

    /**
     * Touch the last accessed timestamp.
     */
    public function touchAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Delete the audio file from storage.
     */
    public function deleteAudioFile(): bool
    {
        if (Storage::exists($this->audio_file_path)) {
            return Storage::delete($this->audio_file_path);
        }

        return false;
    }

    /**
     * Check if the audio file exists.
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->audio_file_path);
    }

    /**
     * Get cache key for this audio chunk.
     */
    public function getCacheKey(): string
    {
        return sprintf(
            'ebook_%d_section_%d_chunk_%d_%s_%.1f',
            $this->ebook_id,
            $this->section_number,
            $this->chunk_number,
            $this->voice,
            $this->speed
        );
    }

    /**
     * Find cached audio for given parameters.
     */
    public static function findCached(
        int $ebookId,
        int $sectionNumber,
        int $chunkNumber,
        string $voice,
        float $speed
    ): ?self {
        return static::where([
            'ebook_id' => $ebookId,
            'section_number' => $sectionNumber,
            'chunk_number' => $chunkNumber,
            'voice' => $voice,
            'speed' => $speed,
        ])->first();
    }

    /**
     * Clean up old unused audio files.
     */
    public static function cleanupOldFiles(int $daysOld = 30): int
    {
        $oldDate = now()->subDays($daysOld);
        
        $oldRecords = static::where('last_accessed_at', '<', $oldDate)
            ->orWhereNull('last_accessed_at')
            ->where('created_at', '<', $oldDate)
            ->get();

        $deletedCount = 0;
        
        foreach ($oldRecords as $record) {
            if ($record->deleteAudioFile()) {
                $record->delete();
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get total cache size for an ebook.
     */
    public static function getEbookCacheSize(int $ebookId): int
    {
        return static::where('ebook_id', $ebookId)->sum('file_size_bytes');
    }

    /**
     * Boot method to clean up files when deleting records.
     */
    protected static function booted(): void
    {
        static::deleting(function ($audioCache) {
            $audioCache->deleteAudioFile();
        });
    }
}