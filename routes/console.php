<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
|
| ScheduleCheck works by comparing a heartbeat against the clock, so the
| scheduler has to actually be running for it to pass -- which is the point.
| It fails loudly if the `scheduler` Compose service stops, rather than the
| silence you would otherwise get from scheduled work simply never happening.
|
*/

Schedule::command(ScheduleCheckHeartbeatCommand::class)
    ->everyMinute()
    ->description('Health: scheduler heartbeat');

// QueueCheck works by dispatching a heartbeat job and confirming a worker
// consumed it, so it needs this to run or it always reports failure.
Schedule::command(DispatchQueueCheckJobsCommand::class)
    ->everyMinute()
    ->description('Health: queue heartbeat');

Schedule::command(RunHealthChecksCommand::class)
    ->everyFiveMinutes()
    ->description('Health: run all checks');
