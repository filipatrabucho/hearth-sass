<?php

namespace App\Domain\Post;

use App\Domain\Client\Client;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public static function validationRules(?int $id = null): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'discord_channel_id' => 'required|string',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'client_id',
            'author_user_id',
            'discord_channel_id',
            'discord_message_id',
            'title',
            'content',
            'status',
            'published_at',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
