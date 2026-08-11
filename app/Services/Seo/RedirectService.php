<?php

namespace App\Services\Seo;

use App\Exceptions\RedirectLoopException;
use App\Models\Redirect;

class RedirectService
{
    /**
     * Backstop against a very long (but technically non-repeating) chain -
     * the $visited check below already catches true cycles (A -> B -> A)
     * on its own; this just bounds worst-case work.
     */
    private const MAX_HOPS = 10;

    /**
     * Resolves $path through as many chained redirects as exist and
     * collapses them into a single Redirect describing the original path
     * -> final destination, using the *first* hop's status_code (serving a
     * client several hops in a row for what should be one redirect is bad
     * practice - this is the standard "flatten the chain" approach).
     *
     * Returns null when $path has no active redirect at all.
     *
     * @throws RedirectLoopException when the chain revisits a path already
     *                               passed through (e.g. A -> B -> A) - stops instead of looping
     *                               forever.
     */
    public function resolve(string $path): ?Redirect
    {
        $entry = $this->find($path);

        if (! $entry) {
            return null;
        }

        $visited = [$path];
        $current = $entry;
        $destination = null;

        while (true) {
            $this->recordHit($current);

            if (in_array($current->to_path, $visited, true) || count($visited) > self::MAX_HOPS) {
                throw new RedirectLoopException($path);
            }

            $visited[] = $current->to_path;
            $destination = $current->to_path;

            $next = $this->find($destination);

            if (! $next) {
                break;
            }

            $current = $next;
        }

        $resolved = clone $entry;
        $resolved->to_path = $destination;

        return $resolved;
    }

    private function find(string $path): ?Redirect
    {
        return Redirect::query()->where('from_path', $path)->where('is_active', true)->first();
    }

    private function recordHit(Redirect $redirect): void
    {
        $redirect->forceFill([
            'hits' => $redirect->hits + 1,
            'last_hit_at' => now(),
        ])->save();
    }
}
