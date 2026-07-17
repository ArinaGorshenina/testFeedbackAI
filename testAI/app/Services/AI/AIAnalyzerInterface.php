<?php

namespace App\Services\AI;

interface AIAnalyzerInterface
{
    /**
     * @return array{sentiment:string, category:string, summary:?string, ai_available:bool}
     */
    public function analyze(string $text): array;
}
