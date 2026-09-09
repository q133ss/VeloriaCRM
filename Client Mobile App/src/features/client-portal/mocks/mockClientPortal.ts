import { ClientServiceCard, HomeFeed, NewsPostSummary } from '../model/types';

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

// Negative ids: real posts from GET /client/posts are always positive, so
// these can never collide with a real post's id if a mock item is tapped
// through to NewsDetailScreen.
const defaultUpdates: NewsPostSummary[] = [
    {
      id: -1,
      title: 'Открылось два окна на пятницу',
      excerpt: 'Освободились места после 17:00. Можно записаться прямо из приложения.',
      body: 'Освободились места после 17:00. Можно записаться прямо из приложения.',
      imageUrl: null,
      date: 'Сегодня',
    },
    {
      id: -2,
      title: 'Весенняя палитра уже в студии',
      excerpt: 'Добавила 12 спокойных оттенков и несколько полупрозрачных баз.',
      body: 'Добавила 12 спокойных оттенков и несколько полупрозрачных баз.',
      imageUrl: null,
      date: '14 марта',
    },
    {
      id: -3,
      title: 'Как держать покрытие дольше',
      excerpt: 'Короткая памятка по уходу после процедуры без сложных правил.',
      body: 'Короткая памятка по уходу после процедуры без сложных правил.',
      imageUrl: null,
      date: '11 марта',
    },
  ];

// `nextAppointment` isn't here: unlike the services/news placeholders below —
// harmless filler until a master has real ones — a fake upcoming appointment
// would be a lie about the client's own booking. HomeScreen always sources it
// from GET /client/appointments (ClientPortalProvider.loadHomeFeed), null when
// there isn't one.
export function buildMockHomeFeed(
  clientName = 'Клиент',
  services: ClientServiceCard[] = defaultServices,
  updates: NewsPostSummary[] = defaultUpdates,
): Omit<HomeFeed, 'nextAppointment'> {
  return {
    clientName,
    services,
    updates,
  };
}
