<?php

/**
 * Настройки главной страницы, которые меняются без правки вёрстки.
 */
return [
    // Ссылка на приложение для клиенток (Google Play или APK). Пока пусто —
    // кнопка «Скачать для Android» на главной не показывается.
    'android_app_url' => env('CLIENT_APP_ANDROID_URL'),

    // Шаблоны сайтов, которые показываем в галерее на главной, по порядку.
    // Слаги — имена файлов в resources/landing-templates/. Несуществующие
    // пропускаются молча, так что удалённый шаблон главную не сломает.
    'featured_templates' => ['pretty', 'sparlex', 'salone', 'master-card', 'wellness', 'energen'],

    // Видео-отзывы мастеров. Пока список пуст, секция на главной не выводится.
    // url    — обычная ссылка на ролик: VK Видео, YouTube (в т.ч. Shorts) или Rutube;
    // poster — обложка 9:16 из public/ (например, images/home/reviews/anna.jpg);
    // name, role, city — подпись под роликом.
    //
    // [
    //     'url' => 'https://vkvideo.ru/video-12345_67890',
    //     'poster' => 'images/home/reviews/anna.jpg',
    //     'name' => 'Анна',
    //     'role' => 'Мастер маникюра',
    //     'city' => 'Казань',
    // ],
    'video_reviews' => [],
];
