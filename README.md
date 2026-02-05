# IPlayGames PHP SDK

High-level PHP SDK for the IPlayGames Game Aggregator API.

## Installation

```bash
composer require iplaygames/sdk-wrapper-php
```

## Quick Start

```php
use IPlayGamesApiClient\Client;

$client = new Client([
    'api_key' => 'your-api-key',
    'base_url' => 'https://api.iplaygames.ai',
]);

// Get games
$response = $client->games()->list(['currency' => 'USD']);
if ($response['success']) {
    foreach ($response['games'] as $game) {
        echo $game['title'] . "\n";
    }
}

// Start a game session
$response = $client->sessions()->start([
    'game_id' => 123,
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => '192.168.1.1',
]);

if ($response['success']) {
    // Redirect player to game
    header("Location: " . $response['game_url']);
}
```

## Configuration

```php
$client = new Client([
    'api_key' => 'your-api-key',            // Required
    'base_url' => 'https://api.iplaygames.ai', // Optional
    'timeout' => 30,                        // Optional, request timeout in seconds
    'verify_ssl' => true,                   // Optional, SSL verification
    'webhook_secret' => 'your-secret',      // Optional, for webhook verification
]);
```

## Response Pattern

All flow methods return arrays with a consistent pattern:

```php
$response = $client->games()->list(['search' => 'bonanza']);

if ($response['success']) {
    // Use the data
    print_r($response['games']);
    print_r($response['meta']);
} else {
    // Handle error
    echo $response['error'];
}
```

## Available Flows

### Games

```php
// List games with filters
$response = $client->games()->list([
    'currency' => 'USD',
    'country' => 'US',
    'category' => 'slots',
    'search' => 'bonanza',
]);
if ($response['success']) {
    foreach ($response['games'] as $game) {
        echo "{$game['title']} by {$game['producer']}\n";
    }
    echo "Total: {$response['meta']['total']}\n";
}

// Get single game
$response = $client->games()->get(123);

// Convenience methods
$response = $client->games()->byProducer(42); // Producer ID (int) or name (string)
$response = $client->games()->byCategory('live');
$response = $client->games()->search('sweet bonanza');
$response = $client->games()->forPlayer('USD', 'US');
```

### Sessions

```php
// Start a game session
$response = $client->sessions()->start([
    'game_id' => 123,
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'locale' => 'en',
    'device' => 'mobile',
    'return_url' => 'https://casino.com/lobby',
]);

if ($response['success']) {
    echo "Session ID: " . $response['session_id'] . "\n";
    echo "Game URL: " . $response['game_url'] . "\n";
}

// Get session status
$statusResponse = $client->sessions()->status($response['session_id']);

// End session
$endResponse = $client->sessions()->end($response['session_id']);

// Start demo session
$demoResponse = $client->sessions()->startDemo(123);
```

### Jackpot

```php
// Get configuration
$configResponse = $client->jackpot()->getConfiguration();

// Get all pools
$poolsResponse = $client->jackpot()->getPools();
if ($poolsResponse['success']) {
    foreach ($poolsResponse['pools'] as $pool) {
        echo "{$pool['pool_type']}: {$pool['total_amount_formatted']}\n";
    }
}

// Get specific pool
$dailyPoolResponse = $client->jackpot()->getPool('daily');

// Get winners
$winnersResponse = $client->jackpot()->getWinners('daily');

// Manage games
$addResponse = $client->jackpot()->addGames('daily', [1, 2, 3]);
$removeResponse = $client->jackpot()->removeGames('daily', [1]);

// Get contributions
$contribResponse = $client->jackpot()->getContributions([
    'player_id' => 'player_456',
]);
```

### Promotions

```php
// List promotions
$promoListResponse = $client->promotions()->list('active', '');

// Get promotion details
$promoResponse = $client->promotions()->get(1);

// Create a promotion
$createResponse = $client->promotions()->create([
    'name' => 'Summer Tournament',
    'promotion_type' => 'tournament',
    'cycle_type' => 'daily',
]);

// Get leaderboard
$leaderboardResponse = $client->promotions()->getLeaderboard(1, 10, 0);

// Opt-in player
$optInResponse = $client->promotions()->optIn(1, 'player_456', 'USD');

// Manage games
$manageResponse = $client->promotions()->manageGames(1, [1, 2, 3]);
```

### Jackpot Widgets

```php
// 1. Register your domain
$domainResponse = $client->jackpotWidget()->registerDomain('casino.example.com', [
    'name' => 'My Casino',
]);

// 2. List registered domains
$domainsResponse = $client->jackpotWidget()->listDomains();

// 3. Create anonymous token (view-only)
$anonTokenResponse = $client->jackpotWidget()->createAnonymousToken('domain_token_here');

// 4. Create player token (can interact)
$playerTokenResponse = $client->jackpotWidget()->createPlayerToken(
    'domain_token_here',
    'player_456',
    'USD'
);

// 5. Get embed code for your frontend
if ($playerTokenResponse['success']) {
    echo $client->jackpotWidget()->getEmbedCode($playerTokenResponse['token'], [
        'theme' => 'dark',
        'container' => 'jackpot-widget',
    ]);
}
```

### Promotion Widgets

```php
// Register domain
$domainResponse = $client->promotionWidget()->registerDomain('casino.example.com');

// Create player token
$tokenResponse = $client->promotionWidget()->createPlayerToken(
    'domain_token',
    'player_456',
    'USD'
);

// Get embed code
if ($tokenResponse['success']) {
    echo $client->promotionWidget()->getEmbedCode($tokenResponse['token'], [
        'theme' => 'dark',
        'container' => 'promo-widget',
    ]);
}
```

### Multi-Session (TikTok-style Game Swiping)

```php
// Start multi-session
$multiResponse = $client->multiSession()->start([
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'device' => 'mobile',
    'game_ids' => ['123', '456', '789'], // Optional: specific games
]);

if ($multiResponse['success']) {
    echo "Swipe URL: " . $multiResponse['swipe_url'] . "\n";
    echo "Total Games: " . $multiResponse['total_games'] . "\n";

    // Get iframe HTML to embed the swipe UI
    echo $client->multiSession()->getIframe($multiResponse['swipe_url'], [
        'width' => '100%',
        'height' => '100vh',
        'id' => 'game-swiper',
    ]);
}

// Get status
$statusResponse = $client->multiSession()->status($multiResponse['multi_session_id']);

// End when player leaves
$endResponse = $client->multiSession()->end($multiResponse['multi_session_id']);
```

## Handling Webhooks

GameHub sends webhooks for transactions. Your casino must implement a webhook endpoint.

### Webhook Types

| Type | Description |
|------|-------------|
| `authenticate` | Verify player exists and get initial data |
| `balance_check` | Get current player balance |
| `bet` | Player placed a bet |
| `win` | Player won money |
| `rollback` | Undo a transaction |
| `reward` | Award from promotions/tournaments |

### Implementing Your Webhook Controller

```php
use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Webhooks\WebhookHandler;

class WebhookController
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'api_key' => env('GAMEHUB_API_KEY'),
            'webhook_secret' => env('GAMEHUB_WEBHOOK_SECRET'),
        ]);
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature');

        // Verify signature
        $handler = $this->client->webhooks();

        if (!$handler->verify($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse webhook
        $webhook = $handler->parse($payload);

        // Handle by type
        switch ($webhook->type) {
            case WebhookHandler::TYPE_AUTHENTICATE:
                return $this->authenticate($webhook);

            case WebhookHandler::TYPE_BALANCE_CHECK:
                return $this->getBalance($webhook);

            case WebhookHandler::TYPE_BET:
                return $this->processBet($webhook);

            case WebhookHandler::TYPE_WIN:
                return $this->processWin($webhook);

            case WebhookHandler::TYPE_ROLLBACK:
                return $this->processRollback($webhook);

            case WebhookHandler::TYPE_REWARD:
                return $this->processReward($webhook);
        }
    }

    private function authenticate($webhook)
    {
        $player = Player::find($webhook->playerId);

        if (!$player) {
            return response()->json(
                $this->client->webhooks()->playerNotFoundResponse()
            );
        }

        $balance = $player->getBalance($webhook->currency);

        return response()->json(
            $this->client->webhooks()->successResponse($balance, [
                'player_name' => $player->name,
            ])
        );
    }

    private function getBalance($webhook)
    {
        $player = Player::find($webhook->playerId);

        if (!$player) {
            return response()->json(
                $this->client->webhooks()->playerNotFoundResponse()
            );
        }

        return response()->json(
            $this->client->webhooks()->successResponse(
                $player->getBalance($webhook->currency)
            )
        );
    }

    private function processBet($webhook)
    {
        $player = Player::find($webhook->playerId);
        $balance = $player->getBalance($webhook->currency);
        $betAmount = $webhook->getAmountInDollars();

        // Check funds
        if ($balance < $betAmount) {
            return response()->json(
                $this->client->webhooks()->insufficientFundsResponse($balance)
            );
        }

        // Check idempotency
        if (Transaction::where('external_id', $webhook->transactionId)->exists()) {
            return response()->json(
                $this->client->webhooks()->alreadyProcessedResponse($balance)
            );
        }

        // Process bet
        DB::transaction(function () use ($player, $webhook, $betAmount) {
            $player->debit($betAmount, $webhook->currency);

            Transaction::create([
                'external_id' => $webhook->transactionId,
                'player_id' => $webhook->playerId,
                'type' => 'bet',
                'amount' => $betAmount,
                'currency' => $webhook->currency,
            ]);
        });

        return response()->json(
            $this->client->webhooks()->successResponse(
                $player->fresh()->getBalance($webhook->currency)
            )
        );
    }

    private function processWin($webhook)
    {
        $player = Player::find($webhook->playerId);
        $winAmount = $webhook->getAmountInDollars();

        // Check idempotency
        if (Transaction::where('external_id', $webhook->transactionId)->exists()) {
            return response()->json(
                $this->client->webhooks()->alreadyProcessedResponse(
                    $player->getBalance($webhook->currency)
                )
            );
        }

        // Process win
        DB::transaction(function () use ($player, $webhook, $winAmount) {
            $player->credit($winAmount, $webhook->currency);

            Transaction::create([
                'external_id' => $webhook->transactionId,
                'player_id' => $webhook->playerId,
                'type' => 'win',
                'amount' => $winAmount,
                'currency' => $webhook->currency,
            ]);
        });

        return response()->json(
            $this->client->webhooks()->successResponse(
                $player->fresh()->getBalance($webhook->currency)
            )
        );
    }

    private function processRollback($webhook)
    {
        // Find original transaction
        $original = Transaction::where('external_id', $webhook->get('original_transaction_id'))->first();

        if (!$original) {
            // Transaction not found - might not have been processed
            $player = Player::find($webhook->playerId);
            return response()->json(
                $this->client->webhooks()->successResponse(
                    $player->getBalance($webhook->currency)
                )
            );
        }

        // Reverse the transaction
        $player = Player::find($webhook->playerId);

        if ($original->type === 'bet') {
            $player->credit($original->amount, $webhook->currency);
        } else {
            $player->debit($original->amount, $webhook->currency);
        }

        $original->update(['status' => 'rolled_back']);

        return response()->json(
            $this->client->webhooks()->successResponse(
                $player->fresh()->getBalance($webhook->currency)
            )
        );
    }

    private function processReward($webhook)
    {
        $player = Player::find($webhook->playerId);
        $rewardAmount = $webhook->getAmountInDollars();

        $player->credit($rewardAmount, $webhook->currency);

        return response()->json(
            $this->client->webhooks()->successResponse(
                $player->fresh()->getBalance($webhook->currency)
            )
        );
    }
}
```

## Webhook Payload Fields

### Common Fields (all webhook types)

```php
$webhook->type;        // 'bet', 'win', 'rollback', 'reward', 'authenticate', 'balance_check'
$webhook->playerId;    // Player's ID in your system
$webhook->currency;    // 'USD', 'EUR', etc.
$webhook->gameId;      // Game ID (nullable)
$webhook->gameType;    // 'slot', 'live', 'table', etc.
$webhook->timestamp;   // ISO 8601 timestamp
```

### Transaction Fields (bet, win, rollback, reward)

```php
$webhook->transactionId;           // Unique transaction ID
$webhook->amount;                  // Amount in cents
$webhook->getAmountInDollars();    // Amount in dollars
$webhook->sessionId;               // Game session ID
$webhook->roundId;                 // Game round ID
```

### Freespin Fields

```php
$webhook->isFreespin;              // Is this a freespin round?
$webhook->freespinId;              // Freespin campaign ID
$webhook->freespinTotal;           // Total freespins awarded
$webhook->freespinsRemaining;      // Remaining freespins
$webhook->freespinRoundNumber;     // Current spin number
$webhook->freespinTotalWinnings;   // Cumulative winnings
```

## Error Handling

```php
$response = $client->sessions()->start([
    'game_id' => 123,
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => '192.168.1.1',
]);

if (!$response['success']) {
    echo "Error: " . $response['error'];
    return;
}

// Use the data
echo "Session ID: " . $response['session_id'];
```

## Running Tests

```bash
composer test
```

## License

MIT
