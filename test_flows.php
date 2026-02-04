<?php
/**
 * Test script for GameHub SDK Wrapper Flows
 *
 * Usage: php test_flows.php
 */

require_once(__DIR__ . '/vendor/autoload.php');

use IPlayGames\Client;

// Configuration
$apiKey = 'YOUR_API_TOKEN';
$baseUrl = 'https://gamehub.test';

echo "=== GameHub SDK Wrapper Test ===\n\n";

// Create client with configurable base URL
$client = new Client([
    'api_key' => $apiKey,
    'base_url' => $baseUrl,
    'verify_ssl' => false, // For local testing
    'webhook_secret' => 'test_secret_for_webhooks',
]);

echo "Base URL: {$client->getBaseUrl()}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 1: Games Flow
echo "--- Test 1: Games Flow ---\n";
try {
    $result = $client->games()->list(['per_page' => 5]);
    echo "Total games fetched: " . count($result['games']) . "\n";

    if (!empty($result['games'])) {
        $game = $result['games'][0];
        echo "First game: [{$game['id']}] {$game['title']} by {$game['producer']}\n";

        // Test get single game
        $singleGame = $client->games()->get($game['id']);
        echo "Single game fetch: {$singleGame['title']}\n";
    }
    echo "✓ Games flow working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Jackpot Flow
echo "--- Test 2: Jackpot Flow ---\n";
try {
    $config = $client->jackpot()->getConfiguration();
    echo "Jackpot configuration fetched\n";

    $pools = $client->jackpot()->getPools();
    echo "Jackpot pools count: " . count($pools) . "\n";

    if (!empty($pools)) {
        $pool = $pools[0];
        echo "First pool: {$pool['pool_type']} - " . ($pool['total_amount_formatted'] ?? 'N/A') . "\n";
    }
    echo "✓ Jackpot flow working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Promotions Flow
echo "--- Test 3: Promotions Flow ---\n";
try {
    $result = $client->promotions()->list();
    echo "Promotions fetched: " . count($result['promotions']) . "\n";

    if (!empty($result['promotions'])) {
        $promo = $result['promotions'][0];
        echo "First promotion: {$promo['name']} ({$promo['type']})\n";
    }
    echo "✓ Promotions flow working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Webhook Handler
echo "--- Test 4: Webhook Handler ---\n";
try {
    $handler = $client->webhooks();

    // Test signature verification
    $testPayload = json_encode([
        'type' => 'bet',
        'player_id' => 'player_456',
        'currency' => 'USD',
        'amount' => 1000,
        'transaction_id' => 12345,
    ]);
    $testSignature = hash_hmac('sha256', $testPayload, 'test_secret_for_webhooks');

    $isValid = $handler->verify($testPayload, $testSignature);
    echo "Signature verification: " . ($isValid ? 'PASS' : 'FAIL') . "\n";

    // Test payload parsing
    $webhook = $handler->parse($testPayload);
    echo "Webhook type: {$webhook->type}\n";
    echo "Player ID: {$webhook->playerId}\n";
    echo "Amount (cents): {$webhook->amount}\n";
    echo "Amount (dollars): " . $webhook->getAmountInDollars() . "\n";
    echo "Is bet: " . ($webhook->isBet() ? 'yes' : 'no') . "\n";

    // Test response helpers
    $successResponse = $handler->successResponse(100.50);
    echo "Success response balance: {$successResponse['balance']} cents\n";

    echo "✓ Webhook handler working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Widget Flow (domain registration)
echo "--- Test 5: Widget Flow ---\n";
try {
    // List existing domains
    $domains = $client->jackpotWidget()->listDomains();
    echo "Existing domains: " . count($domains) . "\n";

    // Get embed code (doesn't require API call)
    $embedCode = $client->jackpotWidget()->getEmbedCode('test_token_here', [
        'theme' => 'dark',
    ]);
    echo "Embed code generated: " . (strlen($embedCode) > 0 ? 'yes' : 'no') . "\n";

    echo "✓ Widget flow working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Multi-Session Flow (status check only, not starting new session)
echo "--- Test 6: Multi-Session Flow ---\n";
try {
    // Generate iframe code (doesn't require API call)
    $iframe = $client->multiSession()->getIframe('https://gamehub.test/play/test-token', [
        'width' => '100%',
        'height' => '600px',
        'class' => 'game-swiper',
    ]);
    echo "Iframe generated: " . (strlen($iframe) > 0 ? 'yes' : 'no') . "\n";

    echo "✓ Multi-Session flow working\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "=== All Tests Complete ===\n";
