<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TestAI Feedback API',
    description: 'REST API формы обратной связи с AI-анализом тональности'
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'API'
)]
class OpenApi
{
}
