<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EbookLibrary extends Model
{
    use HasFactory;

    protected $table = 'ahoy_ebook_library';

    protected $fillable = [
        'title',
        'author',
        'description',
        'cover_image',
        'file_path',
        'file_type',
        'total_sections',
        'total_words',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'total_sections' => 'integer',
        'total_words' => 'integer',
    ];

    /**
     * Get the sections for the ebook.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(EbookSection::class, 'ebook_id')->orderBy('section_number');
    }

    /**
     * Get the reading sessions for the ebook.
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(EbookReadingSession::class, 'ebook_id');
    }

    /**
     * Get the audio cache entries for the ebook.
     */
    public function audioCache(): HasMany
    {
        return $this->hasMany(EbookAudioCache::class, 'ebook_id');
    }

    /**
     * Get the current user's reading session.
     */
    public function currentUserSession(): HasOne
    {
        return $this->hasOne(EbookReadingSession::class, 'ebook_id')
            ->where('user_id', auth()->id());
    }

    /**
     * Check if the ebook has generated audio.
     */
    public function hasGeneratedAudio(): bool
    {
        return $this->audioCache()->exists();
    }

    /**
     * Get the cover image URL.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }

        return asset('storage/' . $this->cover_image);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFileSizeAttribute(): string
    {
        if (!$this->file_path || !file_exists(storage_path('app/' . $this->file_path))) {
            return '0 B';
        }

        $size = filesize(storage_path('app/' . $this->file_path));
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}