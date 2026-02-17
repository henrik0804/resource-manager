<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AutoAssignAction;
use App\Enums\AccessSection;
use App\Http\Requests\AutoAssignRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class AutoAssignController
{
    public function __invoke(AutoAssignRequest $request, AutoAssignAction $action): JsonResponse|RedirectResponse
    {
        $enablePriorityScheduling = $request->user()?->canWriteSection(AccessSection::PriorityScheduling) ?? false;
        $result = $action->handle($enablePriorityScheduling);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => sprintf(
                'Auto-assign completed. Assigned %d tasks, skipped %d.',
                $result['assigned'],
                $result['skipped'],
            ),
            'auto_assign' => $result,
        ]);
    }
}
