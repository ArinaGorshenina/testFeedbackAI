<?php

namespace App\Services;

use App\Mail\ContactOwnerNotification;
use App\Mail\ContactUserConfirmation;
use App\Models\ContactRequest;
use App\Repositories\ContactRepository;
use App\Services\AI\AIAnalyzerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function __construct(
        private readonly ContactRepository $repository,
        private readonly AIAnalyzerInterface $aiAnalyzer,
    ) {}

    public function handle(array $data, string $ip): array
    {
        $analysis = $this->aiAnalyzer->analyze($data['comment']);

        $contact = $this->repository->create([
            ...$data,
            'sentiment'    => $analysis['sentiment'],
            'category'     => $analysis['category'],
            'ai_summary'   => $analysis['summary'],
            'ai_available' => $analysis['ai_available'],
            'ip'           => $ip,
        ]);

        $this->sendNotifications($contact);
        $this->logRequest($contact, $ip);
        $this->bumpMetrics($analysis);

        return [
            'id'           => $contact->id,
            'sentiment'    => $analysis['sentiment'],
            'category'     => $analysis['category'],
            'ai_available' => $analysis['ai_available'],
        ];
    }

    private function sendNotifications(ContactRequest $contact): void
    {
        try {
            Mail::to(config('mail.owner_address', env('CONTACT_OWNER_EMAIL')))
                ->send(new ContactOwnerNotification($contact));

            Mail::to($contact->email)
                ->send(new ContactUserConfirmation($contact));
        } catch (\Throwable $e) {
            // Почта не должна ронять весь запрос — только логируем
            Log::channel('contact')->error('Mail sending failed: ' . $e->getMessage());
        }
    }

    private function logRequest(ContactRequest $contact, string $ip): void
    {
        Log::channel('contact')->info('Contact request processed', [
            'id'        => $contact->id,
            'email'     => $contact->email,
            'ip'        => $ip,
            'sentiment' => $contact->sentiment,
            'category'  => $contact->category,
        ]);
    }

  private function bumpMetrics(array $analysis): void
{
    $path = storage_path('app/metrics.json');

    $metrics = $this->readMetrics($path);

    $metrics['total']++;
    $metrics['by_sentiment'][$analysis['sentiment']] = ($metrics['by_sentiment'][$analysis['sentiment']] ?? 0) + 1;
    $metrics['by_category'][$analysis['category']]   = ($metrics['by_category'][$analysis['category']] ?? 0) + 1;
    if (! $analysis['ai_available']) {
        $metrics['ai_failures']++;
    }

    file_put_contents($path, json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

private function readMetrics(string $path): array
{
    $defaults = ['total' => 0, 'by_sentiment' => [], 'by_category' => [], 'ai_failures' => 0];

    if (! file_exists($path)) {
        return $defaults;
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    // array_merge подставит недостающие ключи, даже если файл пустой, битый или неполный
    return is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
}
}
