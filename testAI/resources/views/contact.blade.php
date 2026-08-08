<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обратная связь</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/main.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
</head>

<body>
    <form class="card" id="contactForm" method="POST">
        @csrf
        <h1>Напишите нам</h1>

        <div class="form-content">
            <label>Имя</label>
            <input type="text" name="name" placeholder="Введите имя" required>

            <label>Телефон</label>
            <input type="tel" placeholder="+7 (___) ___-____" name="phone" class="phone-mask" required>

            <label>Email</label>
            <input type="email" name="email" placeholder="Введите почту" required>

            <label>Комментарий</label>
            <textarea name="comment" placeholder="Напишите нам..." required></textarea>
        </div>


        <button type="submit" id="submitBtn">Отправить</button>

        <div class="msg" id="msg"></div>
        <div class="badges" id="badges"></div>
    </form>

    <script>
        const form = document.getElementById('contactForm');
        const msg = document.getElementById('msg');
        const badges = document.getElementById('badges');
        const btn = document.getElementById('submitBtn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            btn.disabled = true;
            btn.textContent = 'Отправляем...';
            msg.className = 'msg';
            badges.innerHTML = '';

            const payload = Object.fromEntries(new FormData(form));

            try {
                const res = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.status === 429) {
                    msg.textContent = data.message;
                    msg.classList.add('err');
                } else if (res.status === 422) {
                    msg.textContent = Object.values(data.errors).flat().join(' ');
                    msg.classList.add('err');
                } else if (res.ok) {
                    msg.textContent = data.message;
                    msg.classList.add('ok');
                    form.reset();
                    const SENTIMENT_LABELS = {
                        positive: 'позитивная',
                        neutral: 'нейтральная',
                        negative: 'негативная',
                    };

                    if (data.data) {
                        const sentimentLabel = SENTIMENT_LABELS[data.data.sentiment] || data.data.sentiment;
                        badges.innerHTML = `
    <span class="badge">тональность: ${sentimentLabel}</span>
    <span class="badge">категория: ${data.data.category}</span>
    <span class="badge">${data.data.ai_available ? 'AI: онлайн' : 'AI: недоступен (fallback)'}</span>
  `;
                    }
                } else {
                    msg.textContent = data.message || 'Ошибка сервера';
                    msg.classList.add('err');
                }
            } catch (err) {
                msg.textContent = 'Не удалось связаться с сервером.';
                msg.classList.add('err');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Отправить';
            }
        });
    </script>
</body>

</html>
