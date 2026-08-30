<?php

declare(strict_types=1);

use App\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Context;

it('returns a request id header', function (): void {
    $response = $this->get('/up');

    expect($response->headers->get(AssignRequestId::HEADER))->not->toBeEmpty();
});

it('honours an inbound request id so ids survive a proxy', function (): void {
    $response = $this->withHeader(AssignRequestId::HEADER, 'abc-123')->get('/up');

    expect($response->headers->get(AssignRequestId::HEADER))->toBe('abc-123');
});

it('puts the request id in the context that queued jobs inherit', function (): void {
    $this->withHeader(AssignRequestId::HEADER, 'traced-42')
        ->get('/up');

    // The middleware writes to Context, which Laravel propagates into jobs.
    expect(Context::get('request_id'))->toBe('traced-42');
});
