<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://testfeedbackai.local',
        'http://localhost:5173',   // если фронт будет на отдельном dev-сервере (Vite)
    ],
    'allowed_headers' => ['*'],
    'supports_credentials' => false,
];
