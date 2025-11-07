<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function run(Project $project)
    {
        $this->authorize('update', $project);
        // dispatch job
        RunOnPageAudit::dispatch($project);
        return response()->json(['message' => 'audit-queued']);
    }
}
