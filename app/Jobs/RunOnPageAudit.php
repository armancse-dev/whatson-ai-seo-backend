<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class RunOnPageAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = new Client(['timeout' => 15]);
        $res = $client->get($this->project->website_url);
        $html = (string) $res->getBody();
        $crawler = new Crawler($html);

        $title = $crawler->filterXPath('//title')->count() ? $crawler->filterXPath('//title')->text() : null;
        $metaDesc = $crawler->filterXPath('//meta[@name="description"]')->count() ? $crawler->filterXPath('//meta[@name="description"]')->attr('content') : null;
        // headers
        $h1 = $crawler->filter('h1')->count() ? $crawler->filter('h1')->first()->text() : null;
        $images = $crawler->filter('img')->each(function (Crawler $node) {
            return $node->attr('alt') ?? ''; });
        // ... more checks: H2, schema (search for application/ld+json), check for viewport meta, mobile-friendly quick-check by looking for meta viewport
        $schema = $crawler->filterXPath('//script[@type="application/ld+json"]')->each(fn($n) => $n->text());
        $reportData = [
            'title' => $title,
            'meta_description' => $metaDesc,
            'h1' => $h1,
            'images' => $images,
            'schema' => $schema,
            // placeholder for page speed & mobile (we'll enqueue separate job to call PageSpeed API or Lighthouse)
        ];

        $this->project->reports()->create([
            'report_type' => 'onpage-audit',
            'data' => $reportData
        ]);
    }
}
