<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Accounts built in advance from cold outreach
    |--------------------------------------------------------------------------
    |
    | An unclaimed account and its page are deleted after this many days.
    |
    */

    'ttl_days' => (int) env('PROSPECTS_TTL_DAYS', 30),

    // Photos are downloaded by the server only from these hosts (and their
    // subdomains): the URLs come from a scraper, and the server must not be
    // talked into fetching anything else.
    'photo_hosts' => array_values(array_filter(explode(',', (string) env(
        'PROSPECTS_PHOTO_HOSTS',
        'userapi.com,vkuserphoto.ru,vk.com,vk.me,mycdn.me,yclients.com'
    )))),

    'photo_max_bytes' => 10 * 1024 * 1024,

    'max_photos' => 12,
];
