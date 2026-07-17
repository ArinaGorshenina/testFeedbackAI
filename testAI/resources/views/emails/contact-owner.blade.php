@php
$sentimentLabels = ['positive' => 'позитивная', 'neutral' => 'нейтральная', 'negative' => 'негативная'];
@endphp

<h2>Новое обращение с сайта</h2>
<p><strong>Имя:</strong> {{ $contact->name }}</p>
<p><strong>Телефон:</strong> {{ $contact->phone }}</p>
<p><strong>Email:</strong> {{ $contact->email }}</p>
<p><strong>Комментарий:</strong><br>{{ $contact->comment }}</p>
<hr>
<p><strong>AI-анализ</strong> ({{ $contact->ai_available ? 'доступен' : 'fallback, AI недоступен' }}):</p>
<ul>
    <li>Тональность: {{ $sentimentLabels[$contact->sentiment] ?? $contact->sentiment }}</li>
    <li>Категория: {{ $contact->category }}</li>
    @if($contact->ai_summary)
        <li>Резюме: {{ $contact->ai_summary }}</li>
    @endif
</ul>
