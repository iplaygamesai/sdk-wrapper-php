<?php

namespace IPlayGamesApiClient\Sessions;

use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Api\GameSessionsApi;
use IPlayGamesApiClient\Model\StartAGameSessionRequest;
use IPlayGamesApiClient\Model\StartAGameSession201Response;
use IPlayGamesApiClient\Model\GetSessionStatus200Response;
use IPlayGamesApiClient\Model\EndAGameSession200Response;
use IPlayGamesApiClient\ApiException;

/**
 * Sessions Flow
 *
 * Start and manage game sessions for players.
 * Uses the generated SDK for API calls.
 */
class SessionsFlow
{
    protected Client $client;
    protected GameSessionsApi $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = $client->getGameSessionsApi();
    }

    /**
     * Start a new game session
     *
     * @param array $params Session parameters:
     *   - game_id: (required) Game ID to launch
     *   - player_id: (required) Player's unique ID in your system
     *   - currency: (required) Three-letter currency code (ISO 4217)
     *   - country_code: (required) Two-letter country code (ISO 3166-1 alpha-2)
     *   - ip_address: (required) Player's IP address
     *   - locale: (optional) Player's language preference (ISO 639-1)
     *   - device: (optional) Device type: desktop, mobile, tablet
     *   - demo: (optional) Start in demo mode (no real money)
     *   - return_url: (optional) URL to redirect player when they exit game
     *   - provider: (optional) Specific provider slug
     *   - freespin_id: (optional) Freespin campaign ID
     *   - freespin_count: (optional) Number of freespins
     *   - freespin_bet_amount: (optional) Bet amount per freespin
     *   - expire_days: (optional) Days until session expires
     * @return array Session data including game_url
     *
     * @example
     * ```php
     * $session = $client->sessions()->start([
     *     'game_id' => 123,
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     *     'country_code' => 'US',
     *     'ip_address' => '192.168.1.1',
     *     'locale' => 'en',
     *     'device' => 'mobile',
     * ]);
     *
     * // Redirect player to game
     * $gameUrl = $session['game_url'];
     * ```
     */
    public function start(array $params): array
    {
        $required = ['game_id', 'player_id', 'currency', 'country_code', 'ip_address'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        try {
            $request = new StartAGameSessionRequest();
            $request->setGameId((int) $params['game_id']);
            $request->setPlayerId($params['player_id']);
            $request->setCurrency($params['currency']);
            $request->setCountryCode($params['country_code']);
            $request->setIpAddress($params['ip_address']);

            if (isset($params['return_url'])) {
                $request->setReturnUrl($params['return_url']);
            }
            if (isset($params['locale'])) {
                $request->setLocale($params['locale']);
            }
            if (isset($params['device'])) {
                $request->setDevice($params['device']);
            }
            if (isset($params['provider'])) {
                $request->setProvider($params['provider']);
            }
            if (isset($params['freespin_id'])) {
                $request->setFreespinId($params['freespin_id']);
            }
            if (isset($params['freespin_count'])) {
                $request->setFreespinCount((int) $params['freespin_count']);
            }
            if (isset($params['freespin_bet_amount'])) {
                $request->setFreespinBetAmount($params['freespin_bet_amount']);
            }
            if (isset($params['expire_days'])) {
                $request->setExpireDays((int) $params['expire_days']);
            }

            $response = $this->api->startAGameSession($request);

            return $this->formatStartResponse($response);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'session_id' => null,
                'game_url' => null,
            ];
        }
    }

    /**
     * Get session status
     *
     * @param string $sessionId Session ID
     * @return array Session status
     *
     * @example
     * ```php
     * $status = $client->sessions()->status('abc123');
     * echo $status['status']; // "active", "closed", "expired"
     * ```
     */
    public function status(string $sessionId): array
    {
        try {
            $response = $this->api->getSessionStatus($sessionId);

            return $this->formatStatusResponse($sessionId, $response);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'status' => null,
            ];
        }
    }

    /**
     * End a game session
     *
     * @param string $sessionId Session ID
     * @return array Result
     *
     * @example
     * ```php
     * $result = $client->sessions()->end('abc123');
     * ```
     */
    public function end(string $sessionId): array
    {
        try {
            $response = $this->api->endAGameSession($sessionId);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Session ended successfully',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ];
        }
    }

    /**
     * Start a demo session (no authentication required for player)
     *
     * @param int $gameId Game ID
     * @param array $params Additional parameters
     * @return array Demo session data
     */
    public function startDemo(int $gameId, array $params = []): array
    {
        $params['game_id'] = $gameId;
        $params['demo'] = true;
        $params['player_id'] = $params['player_id'] ?? 'demo_' . uniqid();
        $params['currency'] = $params['currency'] ?? 'USD';
        $params['country_code'] = $params['country_code'] ?? 'US';
        $params['ip_address'] = $params['ip_address'] ?? '127.0.0.1';

        return $this->start($params);
    }

    /**
     * Format the start session response
     */
    protected function formatStartResponse(?StartAGameSession201Response $response): array
    {
        if ($response === null) {
            return [
                'success' => false,
                'session_id' => null,
                'game_url' => null,
            ];
        }

        $data = $response->getData();
        if ($data === null) {
            return [
                'success' => true,
                'session_id' => null,
                'game_url' => null,
                'raw' => $this->modelToArray($response),
            ];
        }

        return [
            'success' => true,
            'session_id' => $data->getSessionId(),
            'game_url' => $data->getGameUrl(),
            'expires_at' => method_exists($data, 'getExpiresAt') ? $data->getExpiresAt() : null,
            'raw' => $this->modelToArray($response),
        ];
    }

    /**
     * Format the status response
     */
    protected function formatStatusResponse(string $sessionId, ?GetSessionStatus200Response $response): array
    {
        if ($response === null) {
            return [
                'success' => false,
                'session_id' => $sessionId,
                'status' => null,
            ];
        }

        $data = $response->getData();
        if ($data === null) {
            return [
                'success' => true,
                'session_id' => $sessionId,
                'status' => null,
                'raw' => $this->modelToArray($response),
            ];
        }

        return [
            'success' => true,
            'session_id' => $sessionId,
            'status' => method_exists($data, 'getStatus') ? $data->getStatus() : null,
            'player_id' => method_exists($data, 'getPlayerId') ? $data->getPlayerId() : null,
            'game_id' => method_exists($data, 'getGameId') ? $data->getGameId() : null,
            'game' => method_exists($data, 'getGame') ? $this->modelToArray($data->getGame()) : null,
            'created_at' => method_exists($data, 'getCreatedAt') ? $data->getCreatedAt() : null,
            'raw' => $this->modelToArray($response),
        ];
    }

    /**
     * Convert a model object to array
     */
    protected function modelToArray(mixed $model): array
    {
        if ($model === null) {
            return [];
        }

        if (is_array($model)) {
            return $model;
        }

        if (method_exists($model, 'jsonSerialize')) {
            return (array) $model->jsonSerialize();
        }

        if (method_exists($model, 'toArray')) {
            return $model->toArray();
        }

        return (array) $model;
    }
}
