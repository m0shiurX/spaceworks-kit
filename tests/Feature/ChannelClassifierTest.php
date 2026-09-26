<?php

declare(strict_types=1);

use Spaceworks\Kit\Attribution\AttributionData;
use Spaceworks\Kit\Attribution\ChannelClassifier;
use Spaceworks\Kit\Enums\Channel;

test('first touches are sorted into channels', function (array $touch, Channel $channel, ?string $assistant) {
    $data = AttributionData::fromArray($touch);
    $classifier = app(ChannelClassifier::class);

    expect($classifier->classify($data))->toBe($channel)
        ->and($classifier->aiAssistant($data))->toBe($assistant);
})->with([
    'chatgpt utm_source' => [['utm_source' => 'chatgpt.com'], Channel::AiAssistant, 'ChatGPT'],
    'assistant name as utm_source' => [['utm_source' => 'Perplexity'], Channel::AiAssistant, 'Perplexity'],
    'perplexity referrer on www' => [['referrer' => 'www.perplexity.ai/search/abc'], Channel::AiAssistant, 'Perplexity'],
    'claude referrer' => [['referrer' => 'claude.ai/chat/1'], Channel::AiAssistant, 'Claude'],
    'gemini beats google organic' => [['referrer' => 'gemini.google.com/app'], Channel::AiAssistant, 'Gemini'],
    'assistant wins over paid medium' => [['utm_source' => 'chatgpt.com', 'utm_medium' => 'cpc'], Channel::AiAssistant, 'ChatGPT'],
    'cpc' => [['utm_source' => 'google', 'utm_medium' => 'CPC'], Channel::Paid, null],
    'paid social from facebook' => [['utm_source' => 'fb', 'utm_medium' => 'paid_social', 'referrer' => 'facebook.com/'], Channel::Paid, null],
    'newsletter' => [['utm_source' => 'mailchimp', 'utm_medium' => 'email'], Channel::Email, null],
    'facebook referrer' => [['referrer' => 'l.facebook.com/l.php'], Channel::Social, null],
    'linkedin utm_source' => [['utm_source' => 'linkedin'], Channel::Social, null],
    'google country domain' => [['referrer' => 'www.google.com.bd/'], Channel::Organic, null],
    'bing' => [['referrer' => 'bing.com/search'], Channel::Organic, null],
    'other site' => [['referrer' => 'blog.example.org/post'], Channel::Referral, null],
    'unknown utm_source only' => [['utm_source' => 'partner-newsletter-x'], Channel::Referral, null],
    'lookalike host is not google' => [['referrer' => 'notgoogle.com/'], Channel::Referral, null],
    'nothing' => [[], Channel::Direct, null],
]);

test('assistant hosts come from config', function () {
    config(['attribution.ai_assistants' => ['chat.acme.test' => 'Acme AI']]);

    $classifier = app(ChannelClassifier::class);

    expect($classifier->aiAssistant(AttributionData::fromArray(['referrer' => 'chat.acme.test/x'])))->toBe('Acme AI')
        ->and($classifier->classify(AttributionData::fromArray(['referrer' => 'chatgpt.com/'])))->toBe(Channel::Referral);
});
