<?php
use GuzzleHttp\Client;

class OpenAiService
{
   protected $http;
   protected $apiKey;
   protected $endpoint;

   protected string $key;
   protected string $url;
   protected string $model;
   protected int $maxRetries = 2;
   public function __construct()
   {
      $cfg = config('services.openrouter', []);
        $this->key = $cfg['key'] ?? env('OPENROUTER_API_KEY');
        $this->url = $cfg['url'] ?? env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
        $this->model = $cfg['model'] ?? env('OPENROUTER_MODEL', 'openrouter/auto');
   }

   /**
     * Send chat messages to OpenRouter and return assistant content string.
     *
     * @param array $messages  [['role'=>'system','content'=>'...'], ...]
     * @param array $options   extra payload options (temperature, max_tokens, etc.)
     * @return string
     * @throws \Exception
     */

   public function chat(array $messages, array $options = []): string
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.0,
            'max_tokens' => 1200,
        ], $options);

        $attempt = 0;
        start:
        $attempt++;
        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
                'Content-Type' => 'application/json',
                // optionally pass referer/title for OpenRouter ranking/analytics
                // 'HTTP-Referer' => 'https://your-app.example',
                // 'X-Title' => 'Whatson AI SEO Tools',
            ])->timeout(30)->post($this->url, $payload);

            if ($resp->successful()) {
                $body = $resp->json();

                // Typical OpenRouter/OpenAI chat response shape:
                if (isset($body['choices'][0]['message']['content'])) {
                    return $body['choices'][0]['message']['content'];
                }

                // Sometimes OpenRouter returns different shapes (check docs)
                if (isset($body['output'])) {
                    return is_string($body['output']) ? $body['output'] : json_encode($body['output']);
                }

                // fallback to raw string
                return json_encode($body);
            }

            // Handle rate limits & server errors with retry
            if ($resp->status() == 429 && $attempt <= $this->maxRetries + 1) {
                sleep(2 * $attempt);
                goto start;
            }

            if ($resp->serverError() && $attempt <= $this->maxRetries + 1) {
                sleep(1 * $attempt);
                goto start;
            }

            Log::error('OpenAiService error', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new \Exception("OpenRouter request failed: HTTP {$resp->status()} - {$resp->body()}");
        } catch (Throwable $e) {
            if ($attempt <= $this->maxRetries + 1) {
                sleep(1 * $attempt);
                goto start;
            }
            Log::error('OpenAiService exception', ['exception' => $e->getMessage()]);
            throw $e;
        }
    }
}
