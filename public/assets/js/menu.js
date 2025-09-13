function buildMenu(plan) {
  const menus = {
    Ivory: [
      'Дашборд',
      'Календарь',
      'Записи',
      'Клиенты',
      'Услуги',
      'Инвойсы',
      'Сообщения',
      'Витрина',
      'Портфолио',
      'Отзывы',
      'Аналитика',
      'Интеграции',
      'Настройки',
      'Биллинг',
      'Помощь'
    ],
    Veloria: [
      'Дашборд (Velory)',
      'Календарь (No‑Show/Waitlist)',
      'Записи',
      'Клиенты (Fit Score)',
      'Услуги (рекомендации)',
      'Инвойсы/Оплаты',
      'Сообщения (триггеры)',
      'Маркетинг (Reactivation)',
      'Витрина+Пиксели',
      'Портфолио (Curator)',
      'Отзывы (Maestro)',
      'Аналитика (маржа/час)',
      'Обучение/Тренды',
      'Интеграции (+GCal 1‑way)',
      'Velory Studio',
      'Настройки',
      'Биллинг',
      'Помощь'
    ],
    Imperium: [
      'Дашборд (Profit Map)',
      'Календарь (автозаполнение)',
      'Записи (правила)',
      'Клиенты (планы ухода)',
      'Услуги (динамика цен/времени)',
      'Инвойсы (расшир.)',
      'Сообщения (A/B, сценарии)',
      'Маркетинг PRO',
      'Витрина (домен/белый лейбл)',
      'Портфолио PRO',
      'Отзывы PRO',
      'Аналитика PRO',
      'Обучение PRO',
      'Интеграции PRO',
      'Автоматизации (IFTTT)',
      'Velory Studio PRO',
      'Настройки (расшир.)',
      'Безопасность/Аудит',
      'Биллинг',
      'Помощь'
    ]
  };

  const items = menus[plan] || menus.Ivory;
  const list = document.getElementById('slide-out');
  list.innerHTML = '';
  items.forEach(label => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = '#';
    a.textContent = label;
    li.appendChild(a);
    list.appendChild(li);
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const elems = document.querySelectorAll('.sidenav');
  M.Sidenav.init(elems);

  fetch('/api/v1/auth/me', { credentials: 'include' })
    .then(r => r.json())
    .then(data => {
      const plan = data && data.plan ? data.plan : 'Ivory';
      buildMenu(plan);
    })
    .catch(() => buildMenu('Ivory'));
});
