<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Architecture rules for Keel.
 *
 * These encode the decisions the template is built on, so they stay true as the
 * app grows rather than eroding the first time someone is in a hurry.
 */
arch('debugging helpers never reach a commit')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

arch('actions hold domain logic, not HTTP concerns')
    ->expect('App\Actions')
    ->not->toUse([Request::class, Response::class])
    ->ignoring('App\Actions\Fortify');

arch('models do not depend on controllers')
    ->expect('App\Models')
    ->not->toUse('App\Http\Controllers');

arch('enums are backed so they persist cleanly')
    ->expect('App\Enums')
    ->toBeStringBackedEnums();

arch('controllers are suffixed and final')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('the app avoids globals')
    ->expect('App')
    ->not->toUse(['env', 'compact', 'extract']);

arch('strict types everywhere')
    ->expect('App')
    ->toUseStrictTypes();
