<?php

namespace App\Domain\Lead;

use Illuminate\Database\Eloquent\Model;

/**
 * A "get started" submission from the public marketing site - captured
 * before (or instead of) the person ever becomes a Client. HearthGG
 * follows up on these by hand today; see LeadController.
 */
class Lead extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CONVERTED,
        self::STATUS_ARCHIVED,
    ];

    public const PLAN_INTERESTS = ['free', 'pro', 'enterprise'];

    protected $guarded = [];

    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'discord_username' => 'nullable|string|max:255',
            'server_name' => 'nullable|string|max:255',
            'plan_interest' => 'sometimes|string|in:'.implode(',', self::PLAN_INTERESTS),
            'message' => 'nullable|string|max:2000',
            'source' => 'nullable|string|max:100',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'name',
            'email',
            'discord_username',
            'server_name',
            'plan_interest',
            'message',
            'status',
            'source',
        ];
    }
}
