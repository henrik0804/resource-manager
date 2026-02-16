<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\MapsToTimelineBar;
use App\Enums\AccessSection;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class ScheduleController
{
    public function __invoke(Request $request): Response
    {
        abort_unless(
            $request->user()?->canReadSection(AccessSection::VisualOverview),
            403,
        );

        $precision = $request->string('precision', 'day')->toString();

        $rangeStart = $request->date('start')
            ? CarbonImmutable::parse($request->date('start'))
            : $this->defaultRangeStart($precision);

        $rangeEnd = $request->date('end')
            ? CarbonImmutable::parse($request->date('end'))
            : $this->defaultRangeEnd($rangeStart, $precision);

        $resources = Resource::query()
            ->with([
                'resourceType',
                'taskAssignments' => fn ($query) => $query
                    ->with('task')
                    ->where(fn ($q) => $q
                        ->where('starts_at', '<', $rangeEnd)
                        ->where('ends_at', '>', $rangeStart)
                        ->orWhereHas('task', fn ($tq) => $tq
                            ->where('starts_at', '<', $rangeEnd)
                            ->where('ends_at', '>', $rangeStart))),
                'resourceAbsences' => fn ($query) => $query
                    ->where('starts_at', '<', $rangeEnd)
                    ->where('ends_at', '>', $rangeStart),
            ])
            ->orderBy('name')
            ->get();

        $rows = $resources->map(fn (Resource $resource) => [
            'id' => $resource->id,
            'label' => $resource->name,
            'resourceType' => $resource->resourceType?->name,
            'bars' => $this->collectBars($resource),
        ]);

        return Inertia::render('schedule/Index', [
            'rows' => $rows,
            'rangeStart' => $rangeStart->format('Y-m-d H:i'),
            'rangeEnd' => $rangeEnd->format('Y-m-d H:i'),
            'precision' => $precision,
        ]);
    }

    /**
     * Determine a sensible default start date for the given precision level.
     */
    private function defaultRangeStart(string $precision): CarbonImmutable
    {
        return match ($precision) {
            'day' => CarbonImmutable::now()->startOfDay(),
            default => CarbonImmutable::now()->startOfWeek(),
        };
    }

    /**
     * Determine a sensible default end date for the given precision level.
     */
    private function defaultRangeEnd(CarbonImmutable $start, string $precision): CarbonImmutable
    {
        return match ($precision) {
            'day' => $start->addDays(2),
            'week' => $start->addMonths(3),
            'month' => $start->addYear(),
        };
    }

    /**
     * Collect all timeline bars for a resource from its eagerly-loaded relationships.
     *
     * @return list<array{start: string, end: string, ganttBarConfig: array{id: string, label: string, style: array<string, string>}}>
     */
    private function collectBars(Resource $resource): array
    {
        /** @var Collection<int, MapsToTimelineBar> $items */
        $items = collect()
            ->merge($resource->taskAssignments)
            ->merge($resource->resourceAbsences);

        return $items
            ->map(fn (MapsToTimelineBar $item) => $item->toTimelineBar())
            ->filter(fn (array $bar) => $bar['start'] !== '' && $bar['end'] !== '')
            ->values()
            ->all();
    }
}
