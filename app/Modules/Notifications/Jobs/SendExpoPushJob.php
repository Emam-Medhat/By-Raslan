<?php

namespace App\Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications to Expo push tokens (replaces the legacy
 * pg_net -> Expo push trigger). Runs on the queue.
 */
class SendExpoPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  list<string>  $tokens
     */
    public function __construct(public array $tokens, public string $title, public string $body)
    {
    }

    public function handle(): void
    {
        $messages = collect($this->tokens)
            ->map(fn (string $token) => ['to' => $token, 'title' => $this->title, 'body' => $this->body])
            ->values()
            ->all();

        $response = Http::asJson()->timeout(10)->post('https://exp.host/--/api/v2/push/send', $messages);

        if (! $response->successful()) {
            Log::warning('Expo push failed', ['status' => $response->status()]);
        }
    }
}