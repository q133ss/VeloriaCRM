import { ClientServiceCard, HomeFeed } from '../model/types';

const defaultServices: ClientServiceCard[] = [
  {
    id: 'svc-1',
    title: 'Маникюр express',
    duration: '50 мин',
    price: 'от 2 200 ₽',
    badge: 'Быстро',
  },
  {
    id: 'svc-2',
    title: 'Маникюр + покрытие',
    duration: '1 ч 40 мин',
    price: 'от 3 300 ₽',
  },
  {
    id: 'svc-3',
    title: 'Укрепление гелем',
    duration: '2 ч',
    price: 'от 3 900 ₽',
    badge: 'Топ',
  },
  {
    id: 'svc-4',
    title: 'Снятие + новый цвет',
    duration: '1 ч 50 мин',
    price: 'от 3 600 ₽',
  },
];

const defaultUpdates = [
    {
      id: 'upd-1',
      title: 'Открылось два окна на пятницу',
      excerpt: 'Освободились места после 17:00. Можно записаться прямо из приложения.',
      date: 'Сегодня',
    },
    {
      id: 'upd-2',
      title: 'Весенняя палитра уже в студии',
      excerpt: 'Добавила 12 спокойных оттенков и несколько полупрозрачных баз.',
      date: '14 марта',
    },
    {
      id: 'upd-3',
      title: 'Как держать покрытие дольше',
      excerpt: 'Короткая памятка по уходу после процедуры без сложных правил.',
      date: '11 марта',
    },
  ];

// `nextAppointment` isn't here: unlike the services/news placeholders below —
// harmless filler until a master has real ones — a fake upcoming appointment
// would be a lie about the client's own booking. HomeScreen always sources it
// from GET /client/appointments (ClientPortalProvider.loadHomeFeed), null when
// there isn't one.
export function buildMockHomeFeed(clientName = 'Клиент', services: ClientServiceCard[] = defaultServices): Omit<HomeFeed, 'nextAppointment'> {
  return {
    clientName,
    services,
    updates: defaultUpdates,
  };
}
