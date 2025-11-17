<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Jobs\RunOnPageAudit;
class AuditController extends Controller
{
    public function run(Project $project)
    {
        $this->authorize('view', $project);
        RunOnPageAudit::dispatch($project);
        return response()->json(['message' => 'audit_queued'], 202);
    }
}
