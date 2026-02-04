<?php
/**
 * IPlayGames PHP SDK Integration Tests
 *
 * Usage: php tests/IntegrationTest.php
 * Or: phpunit tests/IntegrationTest.php
 */

require_once(__DIR__ . '/../vendor/autoload.php');

use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Webhooks\WebhookHandler;

// Configuration
$apiKey = getenv('IPLAYGAMES_API_KEY') ?: 'YOUR_API_TOKEN';
$baseUrl = getenv('IPLAYGAMES_BASE_URL') ?: 'https://gamehub.test';
$webhookSecret = 'test_secret_for_webhooks';

// Test results
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "✓ {$name}\n";
        $passed++;
    } catch (Exception $e) {
        echo "✗ {$name}\n";
        echo "  Error: {$e->getMessage()}\n";
        $failed++;
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new Exception($message);
    }
}

echo "=== IPlayGames PHP SDK Tests ===\n\n";
echo "Base URL: {$baseUrl}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Initialize client
$client = new Client([
    'api_key' => $apiKey,
    'base_url' => $baseUrl,
    'verify_ssl' => false,
    'webhook_secret' => $webhookSecret,
]);

// Test 1: Client initialization
test('Client initializes correctly', function () use ($client, $baseUrl) {
    assertTrue($client->getBaseUrl() === $baseUrl, 'Base URL mismatch');
});

// Test 2: Games Flow - List
test('Games flow - list games', function () use ($client) {
    $result = $client->games()->list(['per_page' => 5]);
    assertTrue($result['success'] === true, 'Request should succeed');
    assertTrue(is_array($result['games']), 'Games should be an array');
    assertTrue(isset($result['meta']['total']), 'Meta should have total');
});

// Test 3: Games Flow - Search
test('Games flow - search games', function () use ($client) {
    $result = $client->games()->search('book', ['per_page' => 5]);
    assertTrue($result['success'] === true, 'Search should succeed');
    assertTrue(is_array($result['games']), 'Games should be an array');
});

// Test 4: Webhook Handler - Signature Verification
test('Webhook handler - verify valid signature', function () use ($client, $webhookSecret) {
    $handler = $client->webhooks();
    $payload = json_encode([
        'type' => 'bet',
        'player_id' => 'player_456',
        'currency' => 'USD',
        'amount' => 1000,
    ]);
    $signature = hash_hmac('sha256', $payload, $webhookSecret);
    assertTrue($handler->verify($payload, $signature) === true, 'Valid signature should verify');
});

// Test 5: Webhook Handler - Invalid Signature
test('Webhook handler - reject invalid signature', function () use ($client) {
    $handler = $client->webhooks();
    $payload = json_encode(['type' => 'bet']);
    assertTrue($handler->verify($payload, 'invalid_signature') === false, 'Invalid signature should be rejected');
});

// Test 6: Webhook Handler - Parse Payload
test('Webhook handler - parse payload', function () use ($client) {
    $handler = $client->webhooks();
    $payload = json_encode([
        'type' => 'bet',
        'player_id' => 'player_456',
        'currency' => 'USD',
        'amount' => 1000,
        'transaction_id' => 12345,
    ]);

    $parsed = $handler->parse($payload);
    assertTrue($parsed->type === 'bet', 'Type should be bet');
    assertTrue($parsed->playerId === 'player_456', 'Player ID should match');
    assertTrue($parsed->amount === 1000, 'Amount should be 1000 cents');
});

// Test 7: Webhook Handler - Response Helpers
test('Webhook handler - response helpers', function () use ($client) {
    $handler = $client->webhooks();

    $success = $handler->successResponse(100.50);
    assertTrue($success['status'] === 'success', 'Status should be success');
    assertTrue($success['balance'] === 10050, 'Balance should be in cents');

    $error = $handler->errorResponse('TEST_ERROR', 'Test message');
    assertTrue($error['status'] === 'error', 'Status should be error');
    assertTrue($error['error_code'] === 'TEST_ERROR', 'Error code should match');

    $notFound = $handler->playerNotFoundResponse();
    assertTrue($notFound['error_code'] === 'PLAYER_NOT_FOUND', 'Should be player not found');

    $insufficient = $handler->insufficientFundsResponse(50.25);
    assertTrue($insufficient['error_code'] === 'INSUFFICIENT_FUNDS', 'Should be insufficient funds');
    assertTrue($insufficient['balance'] === 5025, 'Balance should be in cents');
});

// Test 8: Jackpot Widget - Embed Code
test('Jackpot widget - generate embed code', function () use ($client) {
    $embedCode = $client->jackpotWidget()->getEmbedCode('test_token', [
        'theme' => 'dark',
        'container' => 'my-widget',
    ]);
    assertTrue(str_contains($embedCode, 'test_token'), 'Embed code should contain token');
    assertTrue(str_contains($embedCode, 'my-widget'), 'Embed code should contain container ID');
    assertTrue(str_contains($embedCode, 'jackpot.js'), 'Embed code should reference jackpot.js');
});

// Test 9: Promotion Widget - Embed Code
test('Promotion widget - generate embed code', function () use ($client) {
    $embedCode = $client->promotionWidget()->getEmbedCode('test_token', [
        'theme' => 'light',
    ]);
    assertTrue(str_contains($embedCode, 'test_token'), 'Embed code should contain token');
    assertTrue(str_contains($embedCode, 'promotions.js'), 'Embed code should reference promotions.js');
});

// Test 10: Multi-Session - Iframe Generation
test('Multi-session - generate iframe', function () use ($client) {
    $iframe = $client->multiSession()->getIframe('https://example.com/swipe', [
        'width' => '100%',
        'height' => '600px',
        'id' => 'game-swiper',
    ]);
    assertTrue(str_contains($iframe, 'https://example.com/swipe'), 'Iframe should contain URL');
    assertTrue(str_contains($iframe, 'id="game-swiper"'), 'Iframe should have ID');
    assertTrue(str_contains($iframe, 'allowfullscreen'), 'Iframe should allow fullscreen');
});

// Summary
echo "\n=== Test Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

exit($failed > 0 ? 1 : 0);
