<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Auth\LoginChallengeView;
use App\Actions\Auth\LoginConfiguration;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'loginConfiguration' => app(LoginConfiguration::class)->inertia(),
            'loginChallenge' => fn (): ?array => app(LoginChallengeView::class)->handle(
                $request->session()->get(app(LoginConfiguration::class)->sessionKey()),
            ),
            'openLoginModal' => (bool) $request->session()->get('openLoginModal', false),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
