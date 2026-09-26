<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First-touch Cookie
    |--------------------------------------------------------------------------
    |
    | Spaceworks\Kit\Attribution\CaptureAttribution stores the visitor's first
    | touch (landing page, referrer, UTM tags, first seen time) in this
    | cookie on the first full HTML page load and never overwrites it. The
    | cookie is encrypted by EncryptCookies like any other app cookie.
    |
    */

    'cookie' => [
        'name' => env('ATTRIBUTION_COOKIE', 'sw_attr'),
        'lifetime_days' => (int) env('ATTRIBUTION_COOKIE_DAYS', 90),
    ],

    /*
    | Longest stored value, in bytes, for landing page, referrer and UTM tags.
    | It must fit the lead tables' columns (utm_* are 255-character strings)
    | and keeps the encrypted cookie under the 4 KB browser limit.
    */

    'max_length' => 255,

    /*
    |--------------------------------------------------------------------------
    | Channel Rules
    |--------------------------------------------------------------------------
    |
    | Used by Spaceworks\Kit\Attribution\ChannelClassifier. Hosts match exactly or
    | as a parent domain ("perplexity.ai" matches "www.perplexity.ai"); a host
    | ending in ".*" matches any TLD ("google.*" matches "www.google.com.bd").
    | UTM values are compared case-insensitively.
    |
    */

    'ai_assistants' => [
        'chatgpt.com' => 'ChatGPT',
        'chat.openai.com' => 'ChatGPT',
        'perplexity.ai' => 'Perplexity',
        'claude.ai' => 'Claude',
        'gemini.google.com' => 'Gemini',
        'copilot.microsoft.com' => 'Copilot',
        'you.com' => 'You.com',
        'phind.com' => 'Phind',
        'meta.ai' => 'Meta AI',
        'grok.com' => 'Grok',
    ],

    'paid_mediums' => ['cpc', 'ppc', 'paid', 'paid_social', 'paidsocial', 'ads', 'display'],

    'email_mediums' => ['email', 'e-mail', 'newsletter'],

    'social_mediums' => ['social', 'social-media', 'social_media', 'organic_social'],

    'organic_mediums' => ['organic', 'seo'],

    'search_engines' => [
        'google.*',
        'bing.com',
        'duckduckgo.com',
        'search.yahoo.com',
        'yandex.*',
        'baidu.com',
        'ecosia.org',
        'search.brave.com',
        'naver.com',
    ],

    'social_networks' => [
        'facebook.com',
        'fb.com',
        'instagram.com',
        'linkedin.com',
        'lnkd.in',
        'x.com',
        'twitter.com',
        't.co',
        'youtube.com',
        'reddit.com',
        'pinterest.com',
        'tiktok.com',
        'threads.net',
    ],

    /*
    | utm_source values that name a social network without a host.
    */

    'social_sources' => ['facebook', 'fb', 'instagram', 'ig', 'linkedin', 'twitter', 'x', 'youtube', 'reddit', 'tiktok'],

];
