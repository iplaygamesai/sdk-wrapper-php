<?php

namespace IPlayGamesApiClient\Webhooks;

/**
 * Webhook Handler
 *
 * Verify and handle incoming webhooks from GameHub.
 *
 * GameHub sends webhooks for:
 * - authenticate: Player authentication
 * - balance_check: Get player balance
 * - bet: Player placed a bet
 * - win: Player won money
 * - rollback: Transaction rollback
 * - reward: Award from tournaments/campaigns
 */
class WebhookHandler
{
    protected string $secret;

    /**
     * Webhook types
     */
    public const TYPE_AUTHENTICATE = 'authenticate';
    public const TYPE_BALANCE_CHECK = 'balance_check';
    public const TYPE_BET = 'bet';
    public const TYPE_WIN = 'win';
    public const TYPE_ROLLBACK = 'rollback';
    public const TYPE_REWARD = 'reward';

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from X-Signature header
     * @return bool Whether the signature is valid
     *
     * @example
     * ```php
     * $payload = file_get_contents('php://input');
     * $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
     *
     * if (!$handler->verify($payload, $signature)) {
     *     http_response_code(401);
     *     exit('Invalid signature');
     * }
     * ```
     */
    public function verify(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Parse webhook payload
     *
     * @param string $payload Raw request body
     * @return WebhookPayload Parsed payload object
     *
     * @example
     * ```php
     * $webhook = $handler->parse($payload);
     * echo $webhook->type; // "bet"
     * echo $webhook->playerId; // "player_456"
     * echo $webhook->amount; // 1000 (in cents)
     * ```
     */
    public function parse(string $payload): WebhookPayload
    {
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        return new WebhookPayload($data);
    }

    /**
     * Verify and parse webhook in one step
     *
     * @param string $payload Raw request body
     * @param string $signature Signature from X-Signature header
     * @return WebhookPayload Parsed payload
     * @throws \InvalidArgumentException If signature is invalid
     */
    public function verifyAndParse(string $payload, string $signature): WebhookPayload
    {
        if (!$this->verify($payload, $signature)) {
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        return $this->parse($payload);
    }

    /**
     * Create a success response for balance/authenticate webhooks
     *
     * @param float $balance Player balance in dollars (will be converted to cents)
     * @param array $extra Additional response data
     * @return array Response array
     */
    public function successResponse(float $balance, array $extra = []): array
    {
        return array_merge([
            'status' => 'success',
            'balance' => (int) ($balance * 100), // Convert to cents
        ], $extra);
    }

    /**
     * Create an error response
     *
     * @param string $code Error code
     * @param string $message Error message
     * @return array Response array
     */
    public function errorResponse(string $code, string $message): array
    {
        return [
            'status' => 'error',
            'error_code' => $code,
            'error_message' => $message,
        ];
    }

    /**
     * Create a player not found error response
     *
     * @return array Response array
     */
    public function playerNotFoundResponse(): array
    {
        return $this->errorResponse('PLAYER_NOT_FOUND', 'Player not found');
    }

    /**
     * Create an insufficient funds error response
     *
     * @param float $balance Current balance
     * @return array Response array
     */
    public function insufficientFundsResponse(float $balance): array
    {
        return array_merge(
            $this->errorResponse('INSUFFICIENT_FUNDS', 'Insufficient funds'),
            ['balance' => (int) ($balance * 100)]
        );
    }

    /**
     * Create a transaction already processed response (for idempotency)
     *
     * @param float $balance Current balance
     * @return array Response array
     */
    public function alreadyProcessedResponse(float $balance): array
    {
        return $this->successResponse($balance, [
            'already_processed' => true,
        ]);
    }
}

/**
 * Webhook Payload
 *
 * Represents a parsed webhook payload with typed properties.
 */
class WebhookPayload
{
    public string $type;
    public string $playerId;
    public string $currency;
    public ?int $gameId;
    public ?string $gameType;
    public string $timestamp;

    // Transaction fields
    public ?int $transactionId;
    public ?int $amount; // In cents
    public ?string $sessionId;
    public ?string $roundId;

    // Reward fields
    public ?string $rewardType;
    public ?string $rewardTitle;

    // Freespin fields
    public bool $isFreespin;
    public ?string $freespinId;
    public ?int $freespinTotal;
    public ?int $freespinsRemaining;
    public ?int $freespinRoundNumber;
    public ?float $freespinTotalWinnings;

    // Raw data
    public array $raw;

    public function __construct(array $data)
    {
        $this->raw = $data;

        $this->type = $data['type'] ?? '';
        $this->playerId = $data['player_id'] ?? '';
        $this->currency = $data['currency'] ?? '';
        $this->gameId = $data['game_id'] ?? null;
        $this->gameType = $data['game_type'] ?? null;
        $this->timestamp = $data['timestamp'] ?? '';

        // Transaction fields
        $this->transactionId = $data['transaction_id'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->sessionId = $data['session_id'] ?? null;
        $this->roundId = $data['round_id'] ?? null;

        // Reward fields
        $this->rewardType = $data['reward_type'] ?? null;
        $this->rewardTitle = $data['reward_title'] ?? null;

        // Freespin fields
        $this->isFreespin = $data['is_freespin_round'] ?? $data['is_freespin'] ?? false;
        $this->freespinId = $data['freespin_id'] ?? $data['bonus_id'] ?? null;
        $this->freespinTotal = $data['freespin_total'] ?? null;
        $this->freespinsRemaining = $data['freespins_remaining'] ?? $data['freespin_left'] ?? null;
        $this->freespinRoundNumber = $data['freespin_round_number'] ?? null;
        $this->freespinTotalWinnings = $data['freespin_total_winnings'] ?? null;
    }

    /**
     * Check if this is a bet transaction
     */
    public function isBet(): bool
    {
        return $this->type === WebhookHandler::TYPE_BET;
    }

    /**
     * Check if this is a win transaction
     */
    public function isWin(): bool
    {
        return $this->type === WebhookHandler::TYPE_WIN;
    }

    /**
     * Check if this is a rollback transaction
     */
    public function isRollback(): bool
    {
        return $this->type === WebhookHandler::TYPE_ROLLBACK;
    }

    /**
     * Check if this is a reward
     */
    public function isReward(): bool
    {
        return $this->type === WebhookHandler::TYPE_REWARD;
    }

    /**
     * Check if this is an authentication request
     */
    public function isAuthenticate(): bool
    {
        return $this->type === WebhookHandler::TYPE_AUTHENTICATE;
    }

    /**
     * Check if this is a balance check
     */
    public function isBalanceCheck(): bool
    {
        return $this->type === WebhookHandler::TYPE_BALANCE_CHECK;
    }

    /**
     * Get amount in dollars (converts from cents)
     */
    public function getAmountInDollars(): ?float
    {
        return $this->amount !== null ? $this->amount / 100 : null;
    }

    /**
     * Get a value from the raw data
     */
    public function get(string $key, $default = null)
    {
        return $this->raw[$key] ?? $default;
    }
}
