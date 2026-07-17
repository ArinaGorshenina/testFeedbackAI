<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GigaChatAnalyzerService implements AIAnalyzerInterface
{
    private const OAUTH_URL = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
    private const API_URL   = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';
    private const TOKEN_CACHE_KEY = 'gigachat_access_token';

    public function analyze(string $text): array
    {
        $authKey = config('services.gigachat.auth_key');

        if (blank($authKey)) {
            Log::channel('contact')->info('AI skipped: no GIGACHAT_AUTH_KEY set, using fallback.');
            return $this->fallback();
        }

        try {
            $token = $this->getAccessToken($authKey);

            if (blank($token)) {
                return $this->fallback();
            }

            $prompt = <<<PROMPT
Ты — классификатор обращений с сайта техподдержки. Проанализируй текст обращения клиента.
Верни СТРОГО валидный JSON без markdown-обёртки и без пояснений, в точности такой формы:
{"sentiment": "positive|neutral|negative", "category": "жалоба|вопрос|предложение|другое", "summary": "одно предложение на русском"}

Текст обращения: "{$text}"
PROMPT;

            $response = Http::timeout(10)
                ->withOptions(['verify' => (bool) config('services.gigachat.verify_ssl', false)])
                ->withToken($token)
                ->post(self::API_URL, [
                    'model'       => config('services.gigachat.model', 'GigaChat'),
                    'temperature' => 0.2,
                    'messages'    => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::channel('contact')->warning('GigaChat API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fallback();
            }

            $raw   = data_get($response->json(), 'choices.0.message.content', '');
            $clean = trim(preg_replace('/^```json|```$/m', '', (string) $raw));
            $data  = json_decode($clean, true);

            if (! is_array($data) || empty($data['sentiment'])) {
                Log::channel('contact')->warning('GigaChat returned unparsable payload', ['raw' => $raw]);
                return $this->fallback();
            }

            return [
                'sentiment'    => $data['sentiment'],
                'category'     => $data['category'] ?? 'другое',
                'summary'      => $data['summary'] ?? null,
                'ai_available' => true,
            ];
        } catch (\Throwable $e) {
            // Таймаут, недоступность сети, любая другая ошибка — сервис не падает
            Log::channel('contact')->error('AI analysis failed: ' . $e->getMessage());
            return $this->fallback();
        }
    }

    /**
     * Access-токен живёт 30 минут — кешируем на 28, чтобы не дёргать OAuth на каждый запрос формы.
     */
    private function getAccessToken(string $authKey): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(28), function () use ($authKey) {
            $response = Http::timeout(8)
                ->withOptions(['verify' => (bool) config('services.gigachat.verify_ssl', false)])
                ->asForm()
                ->withHeaders([
                    'Accept'        => 'application/json',
                    'RqUID'         => (string) Str::uuid(),
                    'Authorization' => 'Basic ' . $authKey,
                ])
                ->post(self::OAUTH_URL, [
                    'scope' => config('services.gigachat.scope', 'GIGACHAT_API_PERS'),
                ]);

            if (! $response->successful()) {
                Log::channel('contact')->warning('GigaChat OAuth error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function fallback(): array
    {
        return [
            'sentiment'    => 'neutral',
            'category'     => 'другое',
            'summary'      => null,
            'ai_available' => false,
        ];
    }
}
