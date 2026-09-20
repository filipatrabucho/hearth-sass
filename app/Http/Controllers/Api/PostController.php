<?php

namespace App\Http\Controllers\Api;

use App\Domain\Client\Client;
use App\Domain\Post\Post;
use App\Http\Controllers\Controller;
use App\Services\ModelServiceApi;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
        private readonly ModelServiceApi $modelService,
    ) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($client->posts()->latest()->get());
    }

    public function recent(Client $client): JsonResponse
    {
        return response()->json(
            $client->posts()->where('status', Post::STATUS_PUBLISHED)->latest('published_at')->limit(10)->get()
        );
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $this->validate($request, Post::validationRules());

        $post = $this->modelService->create(Post::class, [
            ...$request->all(),
            'client_id' => $client->id,
            'author_user_id' => $request->user()->id,
        ]);

        return response()->json($post, 201);
    }

    public function update(Request $request, Client $client, Post $post): JsonResponse
    {
        $this->assertBelongsToClient($client, $post);
        $this->validate($request, ['title' => 'sometimes|string|max:255', 'content' => 'sometimes|string']);

        return response()->json($this->posts->update($post, $request->only(['title', 'content'])));
    }

    public function publish(Client $client, Post $post): JsonResponse
    {
        $this->assertBelongsToClient($client, $post);

        return response()->json($this->posts->publish($post));
    }

    public function destroy(Client $client, Post $post): JsonResponse
    {
        $this->assertBelongsToClient($client, $post);
        $this->posts->delete($post);

        return response()->json(status: 204);
    }

    private function assertBelongsToClient(Client $client, Post $post): void
    {
        abort_unless($post->client_id === $client->id, 404);
    }
}
