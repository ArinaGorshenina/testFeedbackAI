<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $service) {}

   #[OA\Post(
    path: '/api/contact',
    summary: 'Отправка формы обратной связи',
    tags: ['Contact'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'phone', 'email', 'comment'],
            properties: [
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Иван Иванов'
                ),
                new OA\Property(
                    property: 'phone',
                    type: 'string',
                    example: '+79991234567'
                ),
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    example: 'ivan@example.com'
                ),
                new OA\Property(
                    property: 'comment',
                    type: 'string',
                    example: 'Уточните условия доставки'
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Обращение принято'
        ),
        new OA\Response(
            response: 422,
            description: 'Ошибка валидации'
        ),
        new OA\Response(
            response: 429,
            description: 'Превышен лимит запросов'
        ),
    ]
)]
    public function store(ContactFormRequest $request): JsonResponse
    {
        try {
            $result = $this->service->handle($request->validated(), $request->ip());

            return response()->json([
                'success' => true,
                'message' => 'Спасибо! Ваше обращение принято.',
                'data'    => $result,
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Внутренняя ошибка сервера. Попробуйте позже.',
            ], 500);
        }
    }
}
