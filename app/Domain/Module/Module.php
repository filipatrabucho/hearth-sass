<?php

namespace App\Domain\Module;

use App\Domain\Client\Client;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A Module is a feature area (events, bans, tickets, ...) that can be
 * toggled per client depending on what they pay for.
 */
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): ModuleFactory
    {
        return ModuleFactory::new();
    }

    /**
     * Validation rules for creating/updating a module record.
     */
    public static function validationRules(?int $id = null): array
    {
        return [
            'key' => 'required|string|max:100|unique:modules,key'.($id ? ",{$id}" : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Fields from the current request that are allowed to be written by
     * the generic create/update service (App\Services\ModelServiceApi).
     */
    public static function activeFields(): array
    {
        return [
            'key',
            'name',
            'description',
        ];
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_module')
            ->withPivot('is_enabled', 'enabled_at')
            ->withTimestamps();
    }
}
