<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\AIAnalyzerInterface;
use App\Services\AI\GigaChatAnalyzerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
          $this->app->bind(AIAnalyzerInterface::class, GigaChatAnalyzerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    RateLimiter::for('contact', function ($request) {
        return Limit::perMinutes(
            (int) config('services.contact.rate_minutes', 1),
            (int) config('services.contact.rate_limit', 5)
        )->by($request->ip())->response(function () {
            return response()->json([
                'success' => false,
                'message' => 'Слишком много запросов. Попробуйте через минуту.',
            ], 429);
        });
    });
    }
}
