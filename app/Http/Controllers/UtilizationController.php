<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccessSection;
use App\Services\UtilizationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UtilizationController
{
    public function __invoke(Request $request, UtilizationService $service): Response
    {
        abort_unless(
            $request->user()?->canReadSection(AccessSection::UtilizationView),
            403,
        );

        $granularity = $request->string('granularity', 'week')->toString();

        $start = $request->date('start')
            ? CarbonImmutable::parse($request->date('start'))
            : CarbonImmutable::now()->startOfWeek();

        $end = $request->date('end')
            ? CarbonImmutable::parse($request->date('end'))
            : $this->defaultEnd($start, $granularity);

        $data = $service->calculate($start, $end, $granularity);

        return Inertia::render('utilization/Index', $data);
    }

    private function defaultEnd(CarbonImmutable $start, string $granularity): CarbonImmutable
    {
        return match ($granularity) {
            'day' => $start->addWeeks(2),
            'month' => $start->addMonths(3),
            default => $start->addWeeks(4),
        };
    }
}
