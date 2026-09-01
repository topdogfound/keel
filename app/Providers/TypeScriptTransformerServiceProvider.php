<?php

declare(strict_types=1);

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * Generates TypeScript types from PHP.
 *
 * This is what closes the loop between the two halves of the monolith: with
 * Wayfinder already typing routes and form actions, a renamed DTO property or
 * enum case now breaks `./keel types` instead of reaching the browser as
 * `undefined`.
 *
 * Only classes marked #[TypeScript] are emitted, so the output stays a
 * deliberate contract rather than a dump of every class in app/.
 */
class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path())
            // Written next to the other generated frontend sources, and
            // gitignored alongside them -- ./keel setup and CI both regenerate
            // it before any typecheck runs.
            ->outputDirectory(resource_path('js/types'))
            ->writer(new GlobalNamespaceWriter('generated.d.ts'));
        // No formatter: PrettierFormatter shells out to prettier, which this
        // project does not install (it formats via vite-plus `vp fmt`).
    }
}
