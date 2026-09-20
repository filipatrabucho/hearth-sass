<?php

namespace App\Services;

use App\Domain\Client\Client;
use App\Domain\User\User;
use App\Domain\Warning\Warning;
use App\Services\Discord\MessageService;

class WarningService
{
    public function __construct(private readonly MessageService $messages) {}

    public function warn(Client $client, string $discordUserId, ?User $moderator, string $reason, bool $notifyMember = true): Warning
    {
        $warning = Warning::create([
            'client_id' => $client->id,
            'discord_user_id' => $discordUserId,
            'moderator_id' => $moderator?->id,
            'reason' => $reason,
        ]);

        if ($notifyMember) {
            $this->messages->sendDirect($discordUserId, "Recebeste um aviso em **{$client->name}**: {$reason}");
        }

        return $warning;
    }

    public function resolve(Warning $warning): Warning
    {
        $warning->update(['resolved_at' => now()]);

        return $warning->refresh();
    }
}
