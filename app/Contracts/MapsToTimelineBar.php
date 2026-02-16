<?php

declare(strict_types=1);

namespace App\Contracts;

interface MapsToTimelineBar
{
    /**
     * Map the model to a timeline bar array consumable by the Gantt frontend.
     *
     * @return array{start: string, end: string, ganttBarConfig: array{id: string, label: string, style: array<string, string>}}
     */
    public function toTimelineBar(): array;
}
