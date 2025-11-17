<?php
namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class RunOnPageAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;

    public function __construct(Project $project){
        $this->project = $project;
    }

    public function handle()
    {
        $client = new Client(['timeout' => 20, 'verify' => false]);
        try {
            $res = $client->get($this->project->website_url);
        } catch (\Throwable $e) {
            Log::error('Audit fetch failed', ['project_id'=>$this->project->id,'error'=>$e->getMessage()]);
            $this->project->reports()->create([
                'report_type' => 'onpage-audit',
                'data' => ['status'=>'fetch_error','message'=>$e->getMessage()]
            ]);
            return;
        }

        $html = (string) $res->getBody();
        $crawler = new Crawler($html);

        $title = $crawler->filterXPath('//title')->count() ? trim($crawler->filterXPath('//title')->text()) : null;
        $metaDesc = $crawler->filterXPath('//meta[@name="description"]')->count() ? $crawler->filterXPath('//meta[@name="description"]')->attr('content') : null;
        $h1s = $crawler->filter('h1')->each(fn($n)=>trim($n->text()));
        $h2s = $crawler->filter('h2')->each(fn($n)=>trim($n->text()));
        $images = $crawler->filter('img')->each(fn($n)=>[
            'src'=> $n->attr('src') ?? null,
            'alt'=> $n->attr('alt') ?? ''
        ]);
        $schema = $crawler->filterXPath('//script[@type="application/ld+json"]')->each(fn($n)=>$n->text());

        // quick mobile hint check
        $viewport = $crawler->filterXPath('//meta[@name="viewport"]')->count() ? $crawler->filterXPath('//meta[@name="viewport"]')->attr('content') : null;

        // Save report
        $this->project->reports()->create([
            'report_type'=>'onpage-audit',
            'data'=>[
                'status'=>'ok',
                'title'=>$title,
                'meta_description'=>$metaDesc,
                'h1s'=>$h1s,
                'h2s'=>$h2s,
                'images'=>$images,
                'schema'=>$schema,
                'viewport'=>$viewport,
                'fetched_url'=>$this->project->website_url
            ]
        ]);
    }
}
