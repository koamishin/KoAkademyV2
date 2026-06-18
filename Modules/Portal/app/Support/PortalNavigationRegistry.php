<?php

declare(strict_types=1);

namespace Modules\Portal\Support;

use Illuminate\Http\Request;

final class PortalNavigationRegistry
{
    /**
     * @var list<callable(Request, array<string, mixed>): (array<string, mixed>|list<array<string, mixed>>|null)>
     */
    private array $items = [];

    public function add(callable $factory): self
    {
        $this->items[] = $factory;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    public function forRequest(Request $request, array $context): array
    {
        $items = collect($this->items)
            ->flatMap(function (callable $factory) use ($request, $context): array {
                $result = $factory($request, $context);

                if ($result === null) {
                    return [];
                }

                if (array_is_list($result)) {
                    return $result;
                }

                return [$result];
            })
            ->filter(fn (array $item): bool => filled($item['title'] ?? null) && filled($item['href'] ?? null))
            ->values();

        return $items
            ->groupBy(fn (array $item): string => (string) ($item['section'] ?? 'Platform'))
            ->map(fn ($sectionItems, string $section): array => [
                'label' => $section,
                'items' => $sectionItems
                    ->map(function (array $item): array {
                        unset($item['section']);

                        return $item;
                    })
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
