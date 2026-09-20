<?php

namespace App\Services;

use App\Domain\Post\Post;
use App\Services\Discord\MessageService;

/**
 * A Post is an announcement kept in our DB and mirrored to a Discord
 * channel as a regular bot message once published.
 */
class PostService
{
    public function __construct(private readonly MessageService $messages) {}

    public function publish(Post $post): Post
    {
        $message = $this->messages->send($post->discord_channel_id, "**{$post->title}**\n\n{$post->content}");

        $post->update([
            'status' => Post::STATUS_PUBLISHED,
            'discord_message_id' => $message['id'],
            'published_at' => now(),
        ]);

        return $post->refresh();
    }

    public function update(Post $post, array $changes): Post
    {
        $post->update($changes);

        if ($post->isPublished() && $post->discord_message_id && (isset($changes['title']) || isset($changes['content']))) {
            $this->messages->edit($post->discord_channel_id, $post->discord_message_id, "**{$post->title}**\n\n{$post->content}");
        }

        return $post->refresh();
    }

    public function delete(Post $post): void
    {
        if ($post->isPublished() && $post->discord_message_id) {
            $this->messages->delete($post->discord_channel_id, $post->discord_message_id);
        }

        $post->delete();
    }
}
