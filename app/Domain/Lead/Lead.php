<?php

namespace App\Domain\Lead;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A sign-up from the marketing site's public "get started" form. Not a
 * Client - HearthGG follows up by hand and onboards them separately.
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

    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    /**
     * Validation rules for the public submission - only the fields a
     * visitor actually fills in on the marketing form.
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'discord_username' => 'nullable|string|max:255',
            'server_name' => 'nullable|string|max:255',
            'plan_interest' => 'required|string|in:free,pro,enterprise',
            'message' => 'nullable|string',
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
