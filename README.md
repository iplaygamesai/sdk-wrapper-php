# IPlayGames PHP SDK

High-level PHP SDK for the IPlayGames Game Aggregator API.

## Installation

```bash
composer require iplaygames/sdk-wrapper-php
```

## Quick Start

```php
use IPlayGames\Client;

$client = new Client([
    'api_key' => 'your-api-key',
    'base_url' => 'https://api.gamehub.com', // Configurable!
]);

// Get games
$games = $client->games()->list(['currency' => 'USD']);

// Start a game session
$session = $client->sessions()->start([
    'game_id' => 123,
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => '192.168.1.1',
]);

// Redirect player to game
header("Location: " . $session['game_url']);
```

## Configuration

```php
$client = new Client([
    'api_key' => 'your-api-key',           // Required
    'base_url' => 'https://api.gamehub.com', // Optional, defaults to https://api.gamehub.com
    'timeout' => 30,                        // Optional, request timeout in seconds
    'verify_ssl' => true,                   // Optional, SSL verification
    'webhook_secret' => 'your-secret',      // Optional, for webhook verification
]);
```

## Available Flows

### Games

```php
// List games with filters
$games = $client->games()->list([
    'currency' => 'USD',
    'country' => 'US',
    'category' => 'slots',
    'search' => 'bonanza',
]);

// Get single game
$game = $client->games()->get(123);

// Convenience methods
$games = $client->games()->byProducer('Pragmatic Play');
$games = $client->games()->byCategory('live');
$games = $client->games()->search('sweet bonanza');
$games = $client->games()->forPlayer('USD', 'US');
```

### Sessions

```php
// Start a game session
$session = $client->sessions()->start([
    'game_id' => 123,
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'locale' => 'en',
    'device' => 'mobile',
    'return_url' => 'https://casino.com/lobby',
]);

// Get session status
$status = $client->sessions()->status($session['session_id']);

// End session
$client->sessions()->end($session['session_id']);

// Start demo session
$demo = $client->sessions()->startDemo(123);
```

### Jackpot

```php
// Get configuration
$config = $client->jackpot()->getConfiguration();

// Get all pools
$pools = $client->jackpot()->getPools();

// Get specific pool
$dailyPool = $client->jackpot()->getPool('daily');
$weeklyPool = $client->jackpot()->getPool('weekly');

// Get winners
$winners = $client->jackpot()->getWinners('daily');

// Manage games
$client->jackpot()->addGames('daily', [1, 2, 3]);
$client->jackpot()->removeGames('daily', [1]);
```

### Promotions

```php
// List promotions
$promotions = $client->promotions()->list(['status' => 'active']);

// Get promotion details
$promo = $client->promotions()->get(1);

// Get leaderboard
$leaderboard = $client->promotions()->getLeaderboard(1);

// Opt-in player
$client->promotions()->optIn(1, 'player_456', 'USD');
```

### Jackpot Widgets

```php
// 1. Register your domain
$domain = $client->jackpotWidget()->registerDomain('casino.example.com');
$domainToken = $domain['domain_token'];

// 2. Create anonymous token (view-only)
$token = $client->jackpotWidget()->createAnonymousToken($domainToken);

// 3. Create player token (can start game sessions)
$playerToken = $client->jackpotWidget()->createPlayerToken(
    $domainToken,
    'player_456',
    'USD'
);

// 4. Get embed code for your frontend
echo $client->jackpotWidget()->getEmbedCode($token['token'], [
    'theme' => 'dark',
    'container' => 'jackpot-widget',
]);
```

### Promotion Widgets

```php
// Same flow as jackpot widgets
$domain = $client->promotionWidget()->registerDomain('casino.example.com');
$token = $client->promotionWidget()->createPlayerToken(
    $domain['domain_token'],
    'player_456',
    'USD'
);
echo $client->promotionWidget()->getEmbedCode($token['token']);
```

### Multi-Session (TikTok-style Game Swiping)

```php
// Start multi-session
$multiSession = $client->multiSession()->start([
    'player_id' => 'player_456',
    'currency' => 'USD',
    'country_code' => 'US',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'device' => 'mobile',
]);

// Embed the swipe UI
echo $client->multiSession()->getIframe($multiSession['swipe_url'], [
    'width' => '100%',
    'height' => '100vh',
]);

// Get status
$status = $client->multiSession()->status($multiSession['multi_session_id']);

// End when player leaves
$client->multiSession()->end($multiSession['multi_session_id']);
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
use IPlayGames\Client;
use IPlayGames\Webhooks\WebhookHandler;

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
try {
    $session = $client->sessions()->start([...]);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    // 4xx error
    $response = json_decode($e->getResponse()->getBody(), true);
    echo $response['message'];
} catch (\GuzzleHttp\Exception\ServerException $e) {
    // 5xx error
    echo "Server error, please retry";
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

## License

MIT
# sdk-wrapper-php
