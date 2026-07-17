# TestAI Feedback API

REST API формы обратной связи на Laravel с AI-анализом тональности обращений (GigaChat), rate limiting, файловым логированием и Swagger-документацией.

## Содержание

1. [Как запустить проект](#1-как-запустить-проект)
2. [Стек технологий](#2-стек-технологий)
3. [Архитектура](#3-архитектура)
4. [Реализация API](#4-реализация-api)
5. [AI-интеграция](#5-ai-интеграция)
6. [Что сделано с помощью AI](#6-что-сделано-с-помощью-ai)
7. [Хранение данных](#7-хранение-данных)
8. [Примеры запросов](#8-примеры-запросов)

---

## 1. Как запустить проект

На OSPanel 6.5.0 нужно инициализировать и включить такие модули как: Mail -> smtp4dev, PHP -> PHP-8.5, MySQL -> MySQL-8.0, HTTP -> Apache
В настройках OSPanel Проекты -> выставить  PHP -> PHP-8.5, HTTP -> Apache
В phpMyAdmin сервер MySQL-8.0

### Требования

| Компонент | Версия |
|---|---|
| PHP | 8.5 |
| Laravel | 13.8.0 |
| MySQL | 8.0 |
| Локальное окружение | OSPanel 6.5.0 |
| Модуль почты | smtp4dev |

### Установка

```bash
# 1. Клонировать / распаковать проект в папку home в OSPanel
cd C:\OSPanel\home\testFeedbackAI.local\testAI

# 2. Установить PHP-зависимости
composer install

# 3. Скопировать .env и сгенерировать ключ приложения
cp .env.example .env
php artisan key:generate

# 4. Выполнить миграции
php artisan migrate

# 5. Сгенерировать Swagger-документацию
php artisan l5-swagger:generate
```

### Переменные окружения

Ключевые переменные, которые нужно заполнить в `.env` (полный файл — в корне проекта):

```dotenv
APP_NAME=TestAI
APP_ENV=local
APP_URL=http://testFeedbackAI.local

DB_CONNECTION=mysql
DB_HOST=MySQL-8.0
DB_DATABASE=testAI
DB_USERNAME=root
DB_PASSWORD=

# --- Почта (smtp4dev) ---
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25            # см. реальный порт в модуле smtp4dev своей сборки OSPanel
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@testfeedbackai.local"

# --- AI (GigaChat) ---
GIGACHAT_AUTH_KEY=              # Authorization Key из личного кабинета developers.sber.ru
GIGACHAT_SCOPE=GIGACHAT_API_PERS
GIGACHAT_MODEL=GigaChat
GIGACHAT_VERIFY_SSL=true         # сертификат Минцифры должен быть добавлен в cacert.pem (см. раздел 5)

# --- Форма обратной связи ---
CONTACT_OWNER_EMAIL=owner@testfeedbackai.local
CONTACT_RATE_LIMIT=5
CONTACT_RATE_LIMIT_MINUTES=1
```

Как получить `GIGACHAT_AUTH_KEY`:
1. Зарегистрироваться на https://developers.sber.ru/ через SberID.
2. Создать проект → **GigaChat API** → тариф **Freemium для физлиц**.
3. В разделе «Авторизационные данные» сгенерировать **Authorization Key** (показывается один раз).

### Запуск

Через модуль Apache/Nginx OSPanel — сайт поднимается по адресу из `APP_URL`:

```
http://testFeedbackAI.local
```

Проверка, что всё работает:

```bash
curl http://testFeedbackAI.local/api/health
```

---

## 2. Стек технологий

### Backend

- **Язык:** PHP 8.5
- **Фреймворк:** Laravel 13.8.0
- **База данных:** MySQL 8.0 (через Eloquent ORM)
- **HTTP-клиент:** встроенный `Illuminate\Support\Facades\Http` (Guzzle) — используется и для вызовов GigaChat, и мог бы использоваться для любых внешних API
- **Документация API:** `darkaonline/l5-swagger` (OpenAPI/Swagger UI)
- **Почта:** встроенный Mailable Laravel + smtp4dev как локальный SMTP-перехватчик

### AI

- **Провайдер:** GigaChat API (Сбербанк) — выбран вместо OpenAI/Gemini/Anthropic, так как единственный из рассмотренных вариантов доступен из России без VPN и без зарубежной карты
- **Модель:** `GigaChat` (базовая, входит в Freemium-тариф)
- **Протокол:** REST, OAuth 2.0 (`client_credentials`) поверх HTTP, JSON

### Frontend (демо-страница формы)

- Чистый HTML/CSS/JS без сборщиков — glassmorphism-карточка, `fetch` к API, состояния загрузки/успеха/ошибки

### Инфраструктура

- `.env`-конфигурация, разделённая по слоям (`services.php` для внешних интеграций)
- Логирование в отдельный файл-канал
- CORS через `config/cors.php`
- Rate limiting через именованный `RateLimiter`
- Глобальный обработчик исключений через `bootstrap/app.php` (стиль Laravel 11+/13, без `Handler.php`)

---

## 3. Архитектура

### Структура проекта

```
app/
  Http/
    Controllers/Api/
      ContactController.php      # приём формы
      StatusController.php       # health / metrics
    Requests/
      ContactFormRequest.php     # валидация
  Services/
    ContactService.php           # оркестрация: сохранение → AI → письма → лог → метрики
    AI/
      AIAnalyzerInterface.php    # контракт AI-слоя
      GigaChatAnalyzerService.php
  Repositories/
    ContactRepository.php        # доступ к данным
  Mail/
    ContactOwnerNotification.php
    ContactUserConfirmation.php
  Models/
    ContactRequest.php
resources/views/
  emails/contact-owner.blade.php
  emails/contact-user.blade.php
  contact.blade.php              # демо-форма
routes/
  api.php
  web.php
database/migrations/
  ..._create_contact_requests_table.php
config/
  services.php, logging.php, cors.php
bootstrap/app.php                # исключения, throttle-провайдер
storage/
  app/metrics.json               # статистика (файл)
  logs/contact.log                # лог обращений (файл)
```

### Паттерны проектирования

**Controller → Service → Repository** — классическое разделение ответственности:

- **Controller** — только HTTP-слой: принимает запрос, вызывает сервис, формирует JSON-ответ и коды состояния. Никакой бизнес-логики.
- **Service** (`ContactService`) — оркестрирует весь сценарий обработки обращения: вызывает AI, сохраняет через репозиторий, шлёт письма, пишет лог, обновляет метрики. Это единственное место, где эти шаги связаны вместе.
- **Repository** (`ContactRepository`) — единственная точка доступа к модели `ContactRequest`. Если завтра понадобится вынести хранение в другую БД или добавить кеш — меняется только этот класс.

**Strategy / Dependency Inversion для AI** — `AIAnalyzerInterface` описывает контракт (`analyze(string $text): array`), а `GigaChatAnalyzerService` — единственная текущая реализация. Контроллер и сервис работают только с интерфейсом, не зная, что за AI под капотом:

```php
$this->app->bind(AIAnalyzerInterface::class, GigaChatAnalyzerService::class);
```

Смена провайдера (например, на YandexGPT) — это новый класс с тем же интерфейсом плюс одна изменённая строка в `AppServiceProvider`, без изменений в `ContactController`/`ContactService`.

**Form Request** для валидации — `ContactFormRequest` выносит правила и сообщения из контроллера, Laravel сам возвращает `422` при ошибке.

### Объяснение выбора технологий

| Выбор | Почему |
|---|---|
| Laravel 13 | Уже был поднят проект, богатая экосистема (Http-клиент, Mailable, RateLimiter, Form Requests) закрывает все требования задания без сторонних библиотек, кроме Swagger |
| Слоистая архитектура | Задание явно требует Controllers → Services → Repositories; также упрощает unit-тестирование каждого слоя отдельно |
| GigaChat вместо Gemini/OpenAI | Доступность из РФ без VPN — критичное требование, не техническое предпочтение |
| Файловое хранилище метрик/логов | Задание допускает файлы как основной вариант; JSON-файл достаточен для счётчиков, не требует доп. таблиц |
| MySQL для самих обращений | Задание отмечает БД как плюс; данные обращений структурированы и нужны для истории — реляционная БД подходит лучше, чем плоский файл |

---

## 4. Реализация API

### Эндпоинты

| Метод | Путь | Описание | Rate limit |
|---|---|---|---|
| `POST` | `/api/contact` | Приём формы обратной связи | 5 запросов/минуту с IP |
| `GET` | `/api/health` | Статус сервиса (БД, доступность AI) | — |
| `GET` | `/api/metrics` | Статистика обращений | — |
| `GET` | `/api/documentation` | Swagger UI | — |

### `POST /api/contact`

**Запрос:**
```json
{
  "name": "Иван Иванов",
  "phone": "+79991234567",
  "email": "ivan@example.com",
  "comment": "Когда будет доставка в Москву?"
}
```

**Успешный ответ — `201 Created`:**
```json
{
  "success": true,
  "message": "Спасибо! Ваше обращение принято.",
  "data": {
    "id": 42,
    "sentiment": "neutral",
    "category": "вопрос",
    "ai_available": true
  }
}
```

**Ошибка валидации — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Ошибка валидации",
  "errors": {
    "email": ["Некорректный email."],
    "comment": ["Комментарий не может быть пустым."]
  }
}
```

**Превышен лимит запросов — `429 Too Many Requests`:**
```json
{
  "success": false,
  "message": "Слишком много запросов. Попробуйте через минуту."
}
```

**Внутренняя ошибка — `500 Internal Server Error`:**
```json
{
  "success": false,
  "message": "Внутренняя ошибка сервера. Попробуйте позже."
}
```
(в `APP_DEBUG=true`/`APP_ENV=local` в это поле подставляется реальный текст исключения для отладки)

### `GET /api/health`

```json
{
  "status": "ok",
  "timestamp": "2026-07-17T09:00:00+00:00",
  "services": {
    "database": true,
    "ai": true
  }
}
```

### `GET /api/metrics`

```json
{
  "total": 35,
  "by_sentiment": { "neutral": 30, "positive": 4, "negative": 1 },
  "by_category": { "другое": 20, "вопрос": 10, "жалоба": 5 },
  "ai_failures": 3
}
```

### Валидация

Правила описаны в `ContactFormRequest`:

| Поле | Правила |
|---|---|
| `name` | обязательное, строка, 2–100 символов |
| `phone` | обязательное, формат `+7XXXXXXXXXX` или похожий (regex) |
| `email` | обязательное, валидный email, до 255 символов |
| `comment` | обязательное, 5–2000 символов |

### Обработка ошибок

Единая точка — `bootstrap/app.php` → `withExceptions()`:
- `ValidationException` на `api/*` → `422` с картой ошибок по полям
- Любой другой `Throwable` на `api/*` → JSON с соответствующим HTTP-статусом (берётся из исключения, если есть `getStatusCode()`, иначе `500`)
- В контроллере дополнительно обёрнут `try/catch` вокруг вызова сервиса — гарантирует, что даже неожиданная ошибка (например, обрыв соединения с БД) вернётся клиенту как корректный JSON, а не как HTML-страница ошибки Laravel

---

## 5. AI-интеграция

### Какие AI-инструменты и для чего

**GigaChat API (Сбербанк)** используется для одной функции — **анализ обращения при подаче формы**:
- определение тональности (`positive` / `neutral` / `negative`)
- классификация типа обращения (`жалоба` / `вопрос` / `предложение` / `другое`)
- короткое резюме сути обращения одним предложением

Результат сохраняется вместе с обращением в БД и попадает в письмо владельцу сайта — так он сразу видит приоритет и тип сообщения, не читая длинный текст.

### Промпт

```
Ты — классификатор обращений с сайта техподдержки. Проанализируй текст обращения клиента.
Верни СТРОГО валидный JSON без markdown-обёртки и без пояснений, в точности такой формы:
{"sentiment": "positive|neutral|negative", "category": "жалоба|вопрос|предложение|другое", "summary": "одно предложение на русском"}

Текст обращения: "{текст комментария пользователя}"
```

Температура — `0.2` (низкая, чтобы классификация была стабильной и повторяемой, а не творческой).

### Как реализован fallback

AI-слой спрятан за интерфейсом `AIAnalyzerInterface`, единственный метод — `analyze(string $text): array`, всегда возвращающий один и тот же формат, независимо от того, ответил AI или нет:

```php
[
    'sentiment'    => 'neutral',
    'category'     => 'другое',
    'summary'      => null,
    'ai_available' => false,
]
```

Fallback срабатывает на любом из этапов, и ни один из них не прерывает обработку заявки:

| Что произошло | Реакция |
|---|---|
| `GIGACHAT_AUTH_KEY` не задан | сразу fallback, запрос к GigaChat не отправляется |
| OAuth-запрос за токеном не удался (сеть, неверный ключ) | fallback, ошибка пишется в лог |
| Запрос к `chat/completions` вернул ошибку (сеть, неверная модель, лимиты) | fallback, тело ответа пишется в лог |
| Ответ пришёл, но не парсится как JSON нужной структуры | fallback, "грязный" ответ пишется в лог |
| Любое непредвиденное исключение (таймаут и т.п.) | fallback, текст исключения пишется в лог |

Форма при этом **всегда** отвечает `201` и сохраняет обращение — просто с нейтральными значениями AI-полей вместо реальных. Это проверяется вручную: если временно убрать `GIGACHAT_AUTH_KEY` из `.env`, `data.ai_available` в ответе становится `false`, остальной сценарий (письма, лог, метрики) не меняется.

Токен доступа GigaChat живёт 30 минут — кешируется на 28 минут (`Cache::remember`), чтобы не делать лишний OAuth-запрос на каждое обращение с формы.

---

## 6. Что сделано с помощью AI

Часть кода в этом проекте была написана в паре с AI-ассистентом (Claude, Anthropic) — как это принято при современной разработке, с последующей проверкой и правками руками.

**Сгенерировано с помощью AI и использовано с минимальными правками:**
- Каркас слоистой архитектуры (Controller → Service → Repository → AI-интерфейс)
- Шаблон `ContactService` с оркестрацией шагов (сохранение → AI → письма → лог → метрики)
- Структура промпта для классификации тональности/категории
- Черновик этого README и Swagger-аннотаций

**Пришлось дорабатывать и отлаживать вручную (AI дал только первую версию, реальная причина находилась через собственные логи/тесты):**
- Замена AI-провайдера с Gemini на GigaChat под требование "доступно из России"
- Диагностика цепочки SSL-сертификатов для GigaChat (сертификат Минцифры, разница между CLI и веб-PHP в OSPanel, OPcache, итоговое решение через общий `cacert.pem`)
- Исправление бага с `Undefined array key "total"` в файле метрик

---

## 7. Хранение данных

### Логи

Все обращения и события AI/почты логируются в отдельный канал, не смешиваясь с общим `laravel.log`:

```php
// config/logging.php
'contact' => [
    'driver' => 'single',
    'path'   => storage_path('logs/contact.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
],
```

Пример записи:
```
[2026-07-17 09:00:00] local.INFO: Contact request processed {"id":42,"email":"ivan@example.com","ip":"127.0.0.1","sentiment":"neutral","category":"вопрос"}
```

### Rate limiting

Именованный лимитер, зарегистрированный в `AppServiceProvider::boot()`:

```php
RateLimiter::for('contact', function ($request) {
    return Limit::perMinutes(
        (int) config('services.contact.rate_minutes', 1),
        (int) config('services.contact.rate_limit', 5)
    )->by($request->ip())->response(fn () => response()->json([
        'success' => false,
        'message' => 'Слишком много запросов. Попробуйте через минуту.',
    ], 429));
});
```

Хранилище счётчиков — драйвер кеша из `CACHE_STORE` в `.env` (по умолчанию `database`; можно переключить на `file` одной строкой без изменения кода).

### Статистика

`storage/app/metrics.json` — обновляется атомарно при каждом обращении (`ContactService::bumpMetrics()`), содержит:
- общее число обращений
- разбивку по тональности
- разбивку по категории
- число случаев, когда AI был недоступен (fallback)

Файл самовосстанавливается при повреждении/отсутствии — чтение всегда идёт через `array_merge()` с дефолтной структурой, поэтому отсутствующий ключ не роняет запрос.

### Основные данные обращений

Хранятся в MySQL, таблица `contact_requests` (см. миграцию) — имя, телефон, email, комментарий, результат AI-анализа, IP и таймстампы. Выбор в пользу БД, а не файла — обращения нужно выбирать, фильтровать и потенциально показывать в админке, что для реляционной СУБД естественнее, чем для JSON-файла.

---

## 8. Примеры запросов


### curl

```bash
# Отправка формы
curl -X POST http://testFeedbackAI.local/api/contact \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Иван","phone":"+79991234567","email":"ivan@example.com","comment":"Когда будет доставка в Москву?"}'

# Health-check
curl http://testFeedbackAI.local/api/health

# Метрики
curl http://testFeedbackAI.local/api/metrics

# Проверка rate limiting — выполнить 6 раз подряд, шестой вернёт 429
for i in {1..6}; do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST http://testFeedbackAI.local/api/contact \
    -H "Content-Type: application/json" \
    -d '{"name":"Тест","phone":"+79990000000","email":"test@test.com","comment":"Проверка лимита"}'
done
```
