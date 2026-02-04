<?php

namespace IPlayGamesApiClient;

use IPlayGamesApiClient\Games\GamesFlow;
use IPlayGamesApiClient\Sessions\SessionsFlow;
use IPlayGamesApiClient\Jackpot\JackpotFlow;
use IPlayGamesApiClient\Promotions\PromotionsFlow;
use IPlayGamesApiClient\Widgets\JackpotWidgetFlow;
use IPlayGamesApiClient\Widgets\PromotionWidgetFlow;
use IPlayGamesApiClient\MultiSession\MultiSessionFlow;
use IPlayGamesApiClient\Webhooks\WebhookHandler;
use IPlayGamesApiClient\Configuration;
use IPlayGamesApiClient\Api\GamesApi;
use IPlayGamesApiClient\Api\GameSessionsApi;
use IPlayGamesApiClient\Api\MultiSessionsApi;
use IPlayGamesApiClient\Api\WidgetManagementApi;
use IPlayGamesApiClient\Api\FreespinsApi;
use IPlayGamesApiClient\Api\EndpointsApi;
use IPlayGamesApiClient\Api\GameTransactionsApi;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;

/**
 * IPlayGames SDK Client
 *
 * Main entry point for the IPlayGames API SDK.
 * Provides high-level flows for common operations, built on top of the generated API client.
 *
 * @example
 * ```php
 * $client = new \IPlayGames\Client([
 *     'api_key' => 'your-api-key',
 *     'base_url' => 'https://api.iplaygames.ai',
 * ]);
 *
 * // Get games
 * $games = $client->games()->list(['currency' => 'USD']);
 *
 * // Start a game session
 * $session = $client->sessions()->start([
 *     'game_id' => 123,
 *     'player_id' => 'player_456',
 *     'currency' => 'USD',
 * ]);
 * ```
 */
class Client
{
    protected Configuration $config;
    protected ClientInterface $httpClient;
    protected string $webhookSecret;

    // SDK API instances (lazy-loaded)
    protected ?GamesApi $gamesApi = null;
    protected ?GameSessionsApi $gameSessionsApi = null;
    protected ?MultiSessionsApi $multiSessionsApi = null;
    protected ?WidgetManagementApi $widgetManagementApi = null;
    protected ?FreespinsApi $freespinsApi = null;
    protected ?EndpointsApi $endpointsApi = null;
    protected ?GameTransactionsApi $gameTransactionsApi = null;

    // Flow instances (lazy-loaded)
    protected ?GamesFlow $gamesFlow = null;
    protected ?SessionsFlow $sessionsFlow = null;
    protected ?JackpotFlow $jackpotFlow = null;
    protected ?PromotionsFlow $promotionsFlow = null;
    protected ?JackpotWidgetFlow $jackpotWidgetFlow = null;
    protected ?PromotionWidgetFlow $promotionWidgetFlow = null;
    protected ?MultiSessionFlow $multiSessionFlow = null;
    protected ?WebhookHandler $webhookHandler = null;

    /**
     * Create a new IPlayGames client
     *
     * @param array $options Configuration options:
     *   - api_key: (required) Your API key
     *   - base_url: (optional) API base URL, defaults to https://api.iplaygames.ai
     *   - http_client: (optional) Custom Guzzle client instance
     *   - timeout: (optional) Request timeout in seconds, defaults to 30
     *   - verify_ssl: (optional) Whether to verify SSL certificates, defaults to true
     *   - webhook_secret: (optional) Webhook signing secret for callback verification
     *   - debug: (optional) Enable debug mode for API requests
     */
    public function __construct(array $options)
    {
        if (empty($options['api_key'])) {
            throw new \InvalidArgumentException('api_key is required');
        }

        // Configure the generated SDK
        $this->config = new Configuration();
        $this->config->setAccessToken($options['api_key']);

        if (isset($options['base_url'])) {
            $this->config->setHost(rtrim($options['base_url'], '/'));
        }

        if (isset($options['debug'])) {
            $this->config->setDebug($options['debug']);
        }

        // Create HTTP client
        if (isset($options['http_client'])) {
            $this->httpClient = $options['http_client'];
        } else {
            $this->httpClient = new HttpClient([
                'timeout' => $options['timeout'] ?? 30,
                'verify' => $options['verify_ssl'] ?? true,
            ]);
        }

        // Store webhook secret if provided
        $this->webhookSecret = $options['webhook_secret'] ?? '';
        if (!empty($this->webhookSecret)) {
            $this->webhookHandler = new WebhookHandler($this->webhookSecret);
        }
    }

    /**
     * Get the SDK configuration
     */
    public function getConfig(): Configuration
    {
        return $this->config;
    }

    /**
     * Get the HTTP client
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Get the base URL
     */
    public function getBaseUrl(): string
    {
        return $this->config->getHost();
    }

    // =========================================================================
    // SDK API Accessors (low-level access to generated API classes)
    // =========================================================================

    /**
     * Get the Games API instance
     */
    public function getGamesApi(): GamesApi
    {
        if ($this->gamesApi === null) {
            $this->gamesApi = new GamesApi($this->httpClient, $this->config);
        }
        return $this->gamesApi;
    }

    /**
     * Get the Game Sessions API instance
     */
    public function getGameSessionsApi(): GameSessionsApi
    {
        if ($this->gameSessionsApi === null) {
            $this->gameSessionsApi = new GameSessionsApi($this->httpClient, $this->config);
        }
        return $this->gameSessionsApi;
    }

    /**
     * Get the Multi Sessions API instance
     */
    public function getMultiSessionsApi(): MultiSessionsApi
    {
        if ($this->multiSessionsApi === null) {
            $this->multiSessionsApi = new MultiSessionsApi($this->httpClient, $this->config);
        }
        return $this->multiSessionsApi;
    }

    /**
     * Get the Widget Management API instance
     */
    public function getWidgetManagementApi(): WidgetManagementApi
    {
        if ($this->widgetManagementApi === null) {
            $this->widgetManagementApi = new WidgetManagementApi($this->httpClient, $this->config);
        }
        return $this->widgetManagementApi;
    }

    /**
     * Get the Freespins API instance
     */
    public function getFreespinsApi(): FreespinsApi
    {
        if ($this->freespinsApi === null) {
            $this->freespinsApi = new FreespinsApi($this->httpClient, $this->config);
        }
        return $this->freespinsApi;
    }

    /**
     * Get the Endpoints API instance (for jackpots, promotions, etc.)
     */
    public function getEndpointsApi(): EndpointsApi
    {
        if ($this->endpointsApi === null) {
            $this->endpointsApi = new EndpointsApi($this->httpClient, $this->config);
        }
        return $this->endpointsApi;
    }

    /**
     * Get the Game Transactions API instance
     */
    public function getGameTransactionsApi(): GameTransactionsApi
    {
        if ($this->gameTransactionsApi === null) {
            $this->gameTransactionsApi = new GameTransactionsApi($this->httpClient, $this->config);
        }
        return $this->gameTransactionsApi;
    }

    // =========================================================================
    // High-Level Flow Accessors (Stripe-style convenience methods)
    // =========================================================================

    /**
     * Games flow - List and retrieve games
     *
     * @example
     * ```php
     * $games = $client->games()->list(['currency' => 'USD', 'country' => 'US']);
     * $game = $client->games()->get(123);
     * ```
     */
    public function games(): GamesFlow
    {
        if ($this->gamesFlow === null) {
            $this->gamesFlow = new GamesFlow($this);
        }
        return $this->gamesFlow;
    }

    /**
     * Sessions flow - Start and manage game sessions
     *
     * @example
     * ```php
     * $session = $client->sessions()->start([
     *     'game_id' => 123,
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     *     'country_code' => 'US',
     *     'ip_address' => '192.168.1.1',
     * ]);
     *
     * $status = $client->sessions()->status($session['session_id']);
     * $client->sessions()->end($session['session_id']);
     * ```
     */
    public function sessions(): SessionsFlow
    {
        if ($this->sessionsFlow === null) {
            $this->sessionsFlow = new SessionsFlow($this);
        }
        return $this->sessionsFlow;
    }

    /**
     * Jackpot flow - Configure and manage jackpot pools
     *
     * @example
     * ```php
     * $config = $client->jackpot()->getConfiguration();
     * $pools = $client->jackpot()->getPools();
     * $pool = $client->jackpot()->getPool('daily');
     * ```
     */
    public function jackpot(): JackpotFlow
    {
        if ($this->jackpotFlow === null) {
            $this->jackpotFlow = new JackpotFlow($this);
        }
        return $this->jackpotFlow;
    }

    /**
     * Promotions flow - Create and manage promotions
     *
     * @example
     * ```php
     * $promotions = $client->promotions()->list();
     * $promo = $client->promotions()->get(1);
     * $leaderboard = $client->promotions()->getLeaderboard(1);
     * ```
     */
    public function promotions(): PromotionsFlow
    {
        if ($this->promotionsFlow === null) {
            $this->promotionsFlow = new PromotionsFlow($this);
        }
        return $this->promotionsFlow;
    }

    /**
     * Jackpot Widget flow - Manage widget tokens and domains for jackpot displays
     *
     * @example
     * ```php
     * // Register a domain
     * $domain = $client->jackpotWidget()->registerDomain('casino.example.com');
     *
     * // Create anonymous token
     * $token = $client->jackpotWidget()->createToken($domain['domain_token']);
     *
     * // Create player-specific token
     * $playerToken = $client->jackpotWidget()->createToken($domain['domain_token'], [
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     * ]);
     * ```
     */
    public function jackpotWidget(): JackpotWidgetFlow
    {
        if ($this->jackpotWidgetFlow === null) {
            $this->jackpotWidgetFlow = new JackpotWidgetFlow($this);
        }
        return $this->jackpotWidgetFlow;
    }

    /**
     * Promotion Widget flow - Manage widget tokens for promotion displays
     *
     * @example
     * ```php
     * $domain = $client->promotionWidget()->registerDomain('casino.example.com');
     * $token = $client->promotionWidget()->createToken($domain['domain_token']);
     * ```
     */
    public function promotionWidget(): PromotionWidgetFlow
    {
        if ($this->promotionWidgetFlow === null) {
            $this->promotionWidgetFlow = new PromotionWidgetFlow($this);
        }
        return $this->promotionWidgetFlow;
    }

    /**
     * Multi-Session flow - TikTok-style game swiping
     *
     * @example
     * ```php
     * $multiSession = $client->multiSession()->start([
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     *     'country_code' => 'US',
     *     'ip_address' => '192.168.1.1',
     * ]);
     *
     * // Get the swipe URL to embed in iframe
     * $swipeUrl = $multiSession['swipe_url'];
     *
     * // End all sessions when player leaves
     * $client->multiSession()->end($multiSession['multi_session_id']);
     * ```
     */
    public function multiSession(): MultiSessionFlow
    {
        if ($this->multiSessionFlow === null) {
            $this->multiSessionFlow = new MultiSessionFlow($this);
        }
        return $this->multiSessionFlow;
    }

    /**
     * Webhook handler - Verify and handle callbacks from IPlayGames
     *
     * @example
     * ```php
     * // In your webhook controller
     * $payload = file_get_contents('php://input');
     * $signature = $_SERVER['HTTP_X_SIGNATURE'];
     *
     * if ($client->webhooks()->verify($payload, $signature)) {
     *     $data = json_decode($payload, true);
     *     $type = $data['type'];
     *
     *     switch ($type) {
     *         case 'bet':
     *             return $this->handleBet($data);
     *         case 'win':
     *             return $this->handleWin($data);
     *     }
     * }
     * ```
     */
    public function webhooks(): WebhookHandler
    {
        if ($this->webhookHandler === null) {
            throw new \RuntimeException('Webhook secret not configured. Pass webhook_secret in client config.');
        }
        return $this->webhookHandler;
    }

    /**
     * Create webhook handler with a specific secret
     */
    public function createWebhookHandler(string $secret): WebhookHandler
    {
        return new WebhookHandler($secret);
    }
}
