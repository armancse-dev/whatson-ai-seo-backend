<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ClusterKeywordsJob;
use App\Models\Project;
use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordClusterController extends Controller
{
    public function start(Project $project, Request $request)
    {
        $this->authorize('view', $project);

        $request->validate([
            'max_clusters' => 'nullable|integer|min:1|max:50'
        ]);

        ClusterKeywordsJob::dispatch($project, [
            'max_clusters' => $request->input('max_clusters', 10)
        ]);

        return response()->json([
            'message' => 'clustering_queued',
            'project_id' => $project->id
        ], 202);
    }

    public function store(Project $project, Request $request)
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'keywords'   => 'required|array|min:1',
            'keywords.*' => 'string|min:1'
        ]);

        foreach ($data['keywords'] as $kw) {
            $kw = trim($kw);
            if ($kw === '') { continue; }
            Keyword::firstOrCreate([
                'project_id' => $project->id,
                'keyword' => $kw,
            ]);
        }

        ClusterKeywordsJob::dispatch($project);

        return response()->json([
            'status' => 'queued',
            'message' => 'Keyword clustering started successfully.',
            'project_id' => $project->id
        ], 202);
    }

    public function index(Project $project, Request $request)
    {
        $this->authorize('view', $project);

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $q = trim((string) $request->query('q', ''));

        $query = $project->keywords()->orderByDesc('id');
        if ($q !== '') {
            $query->where('keyword', 'like', "%$q%");
        }

        $keywords = $query->paginate($perPage);
        return response()->json($keywords);
    }
}
