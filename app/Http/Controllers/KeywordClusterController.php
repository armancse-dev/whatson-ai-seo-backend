<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ClusterKeywordsJob;
use App\Models\Project;
use Illuminate\Http\Request;
class KeywordClusterController extends Controller
{
    public function start(Project $project, Request $request)
    {
        $this->authorize('view', $project);

        // Optional: validate request for scope or custom params
        $request->validate([
            'max_clusters' => 'nullable|integer|min:1|max:50'
        ]);

        // Dispatch job - queueable
        ClusterKeywordsJob::dispatch($project, [
            'max_clusters' => $request->input('max_clusters', 10)
        ]);

        return response()->json([
            'message' => 'clustering_queued',
            'project_id' => $project->id
        ], 202);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'keywords'   => 'required|array|min:1',
        ]);

        foreach ($data['keywords'] as $kw) {
            Keyword::firstOrCreate([
                'project_id' => $data['project_id'],
                'name' => trim($kw),
            ]);
        }

        ClusterKeywordsJob::dispatch($data['project_id']);

        return response()->json([
            'status' => 'queued',
            'message' => 'Keyword clustering started successfully.'
        ]);
    }

    
}
