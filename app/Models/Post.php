<?php

namespace App\Models;

use App\Support\PostHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const CATEGORY_FOOD = 'food';

    public const CATEGORY_EVENT = 'event';

    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_STORY = 'story';

    public const CATEGORIES = [
        self::CATEGORY_FOOD,
        self::CATEGORY_EVENT,
        self::CATEGORY_PROMOTION,
        self::CATEGORY_STORY,
    ];

    protected $fillable = [
        'author_user_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'content_format',
        'featured_image_path',
        'status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path
            ? Storage::disk('public')->url($this->featured_image_path)
            : null;
    }

    public function getContentHtmlAttribute(): string
    {
        if ($this->content_format === 'html') {
            return app(PostHtml::class)->clean($this->content ?? '');
        }

        return collect(preg_split('/\R{2,}/u', trim($this->content ?? '')) ?: [])
            ->map(fn ($paragraph) => '<p>'.nl2br(e($paragraph)).'</p>')->implode("\n");
    }
}
