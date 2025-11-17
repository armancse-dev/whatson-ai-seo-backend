<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Keyword;
use App\Models\Report;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClusterKeywordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;
    public array $options;

    public $tries = 3;
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     * @param array $options
     */
    public function __construct(Project $project, array $options = [])
    {
        $this->project = $project;
        $this->options = $options;
        $this->onQueue('default');
    }

    public function handle(OpenAiService $openAi)
    {
        $keywords = $this->project->keywords()->pluck('keyword')->map(fn($k)=>trim($k))->filter()->values()->all();

        if (count($keywords) === 0) {
            Log::warning("Project {$this->project->id} has no keywords to cluster.");
            return;
        }

        // Build prompt - ask for JSON output
        $maxClusters = intval($this->options['max_clusters'] ?? 10);
        $prompt = $this->buildPrompt($keywords, $maxClusters);

        $messages = [
            ['role' => 'system', 'content' => "You are an SEO assistant that groups keywords into topical clusters and returns valid JSON."],
            ['role' => 'user', 'content' => $prompt],
        ];

        $raw = $openAi->chat($messages, [
            'temperature' => 0.0,
            'max_tokens' => 1200,
        ]);

        // Try to extract JSON from the output robustly
        $jsonText = $this->extractJson($raw);

        if (is_null($jsonText)) {
            // Save a report with raw text and mark as failed to parse
            $this->project->reports()->create([
                'report_type' => 'keyword-cluster',
                'data' => [
                    'status' => 'parse_error',
                    'raw' => $raw,
                ],
            ]);
            Log::error("ClusterKeywordsJob: could not parse JSON for project {$this->project->id}");
            return;
        }

        $clusters = json_decode($jsonText, true);

        if (!is_array($clusters)) {
            $this->project->reports()->create([
                'report_type' => 'keyword-cluster',
                'data' => [
                    'status' => 'invalid_json',
                    'raw' => $raw,
                    'json_text' => $jsonText,
                ],
            ]);
            Log::error("ClusterKeywordsJob: invalid JSON decoded for project {$this->project->id}");
            return;
        }

        // Persist cluster assignments: update keywords table cluster_group
        foreach ($clusters as $cluster) {
            // Expect cluster to be {"cluster_name":"...","keywords":["k1","k2",...]}
            $clusterName = $cluster['cluster_name'] ?? $cluster['name'] ?? null;
            $clusterKeywords = $cluster['keywords'] ?? $cluster['kw'] ?? $cluster['items'] ?? [];

            if (!$clusterName || !is_array($clusterKeywords)) {
                continue;
            }
            foreach ($clusterKeywords as $kw) {
                $q = trim($kw);
                if ($q === '') continue;
                // find keyword in DB by project and update cluster_group
                Keyword::where('project_id', $this->project->id)
                    ->whereRaw('LOWER(keyword) = ?', [mb_strtolower($q)])
                    ->update(['cluster_group' => $clusterName]);
            }
        }

        // Save final report with clusters
        $this->project->reports()->create([
            'report_type' => 'keyword-cluster',
            'data' => [
                'status' => 'ok',
                'clusters' => $clusters,
                'raw' => $raw,
            ],
        ]);
    }

    /**
     * Build the user prompt to send to the LLM.
     */
    protected function buildPrompt(array $keywords, int $maxClusters): string
    {
        $list = collect($keywords)->map(fn($k)=>"- " . $k)->join("\n");
        $instructions = <<<PROMPT
Group the following keywords into topical clusters (max {$maxClusters} clusters). For each cluster, return a JSON object with fields:
- cluster_name: short, descriptive name (3 words max)
- keywords: array of keywords that belong to this cluster

Return an array of cluster objects only, e.g.:
[
  {"cluster_name":"...", "keywords":["...","..."]},
  {"cluster_name":"...", "keywords":["...","..."]}
]

Do not include additional commentary. If you include anything else, ensure the JSON is extractable. Keywords:
{$list}
PROMPT;
        return $instructions;
    }

    /**
     * Extract JSON array/object from LLM output using regex heuristics.
     */
    protected function extractJson(string $text): ?string
    {
        // first try to find the first occurrence of JSON array '[' ... ']'
        $text = trim($text);

        // Try to find JSON starting from first bracket
        $start = strpos($text, '[');
        $end = strrpos($text, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($text, $start, $end - $start + 1);
            // quick sanity check
            if ($this->looksLikeJson($candidate)) return $candidate;
        }

        // Try curly braces top-level
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($text, $start, $end - $start + 1);
            if ($this->looksLikeJson($candidate)) return $candidate;
        }

        // If nothing found, give up
        return null;
    }

    protected function looksLikeJson(string $s): bool
    {
        // Basic check: valid JSON decode?
        json_last_error(); // reset
        @json_decode($s);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}
