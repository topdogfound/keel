<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * A trivial job used to prove the queue path works end to end.
 * `./keel new` leaves this in place; it is harmless and useful.
 */
class PingJob extends BaseJob
{
    public function __construct(private readonly string $token) {}

    public function handle(): void
    {
        Cache::put("ping:{$this->token}", true, 300);

        Log::info('PingJob handled', ['token' => $this->token]);
    }
}
