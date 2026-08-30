<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Conventions every queued job in this application inherits.
 *
 * The important one is $afterCommit. Dispatching inside a transaction and
 * having the worker pick the job up before that transaction commits is the
 * classic Laravel queue bug: the job runs, cannot find the row it was handed,
 * and fails in a way that looks random. Implementing ShouldQueueAfterCommit
 * removes that whole class of bug rather than relying on everyone remembering.
 *
 * The interface is used rather than the $afterCommit property because
 * Illuminate\Bus\Queueable already declares that property with no default, and
 * PHP rejects a redeclaration whose default differs.
 */
abstract class BaseJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Give up rather than retrying forever on a persistent failure.
     */
    public int $tries = 3;

    /**
     * Surface a timeout as a failure instead of silently retrying.
     */
    public bool $failOnTimeout = true;

    /**
     * Back off progressively instead of hammering a struggling dependency.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }
}
