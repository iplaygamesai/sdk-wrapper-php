<?php

namespace IPlayGames\MultiSession;

use IPlayGames\Client;
use IPlayGamesApiClient\Api\MultiSessionsApi;
use IPlayGamesApiClient\Model\StartAMultiSessionRequest;
use IPlayGamesApiClient\Model\StartAMultiSession201Response;
use IPlayGamesApiClient\Model\GetMultiSessionStatus200Response;
use IPlayGamesApiClient\Model\EndMultiSession200Response;
use IPlayGamesApiClient\ApiException;

/**
 * Multi-Session Flow
 *
 * Manage TikTok-style game swiping experiences for players.
 * Uses the generated SDK for API calls.
 *
 * Multi-sessions allow players to discover games by swiping through them,
 * with sessions created lazily as they navigate.
 */
class MultiSessionFlow
{
    protected Client $client;
    protected MultiSessionsApi $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = $client->getMultiSessionsApi();
    }

    /**
     * Start a multi-session for a player
     *
     * @param array $params Session parameters:
     *   - player_id: (required) Player's unique ID
     *   - currency: (required) Three-letter currency code
     *   - country_code: (required) Two-letter country code
     *   - ip_address: (required) Player's IP address
     *   - game_ids: (optional) Specific game IDs to include
     *   - locale: (optional) Player's language preference
     *   - device: (optional) Device type: desktop, mobile, tablet
     * @return array Multi-session data including swipe_url
     *
     * @example
     * ```php
     * $multiSession = $client->multiSession()->start([
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     *     'country_code' => 'US',
     *     'ip_address' => '192.168.1.1',
     *     'device' => 'mobile',
     * ]);
     *
     * // Embed the swipe URL in an iframe
     * $swipeUrl = $multiSession['swipe_url'];
     * echo "<iframe src='{$swipeUrl}' width='100%' height='100%'></iframe>";
     * ```
     */
    public function start(array $params): array
    {
        $required = ['player_id', 'currency', 'country_code', 'ip_address'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        try {
            $request = new StartAMultiSessionRequest();
            $request->setPlayerId($params['player_id']);
            $request->setCurrency($params['currency']);
            $request->setCountryCode($params['country_code']);
            $request->setIpAddress($params['ip_address']);

            if (isset($params['game_ids'])) {
                $request->setGameIds($params['game_ids']);
            }
            if (isset($params['locale'])) {
                $request->setLocale($params['locale']);
            }
            if (isset($params['device'])) {
                $request->setDevice($params['device']);
            }

            $response = $this->api->startAMultiSession($request);

            return $this->formatStartResponse($response);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'multi_session_id' => null,
                'swipe_url' => null,
            ];
        }
    }

    /**
     * Start a multi-session with specific games
     *
     * @param array $gameIds Game IDs to include
     * @param array $params Player parameters
     * @return array Multi-session data
     */
    public function startWithGames(array $gameIds, array $params): array
    {
        $params['game_ids'] = $gameIds;
        return $this->start($params);
    }

    /**
     * Start a multi-session with random games from all providers
     *
     * @param array $params Player parameters
     * @return array Multi-session data
     */
    public function startRandom(array $params): array
    {
        // Don't pass game_ids to get random games from each provider
        unset($params['game_ids']);
        return $this->start($params);
    }

    /**
     * Get multi-session status
     *
     * @param string $token Multi-session token/ID
     * @return array Session status and game list
     *
     * @example
     * ```php
     * $status = $client->multiSession()->status($multiSessionId);
     * echo $status['status']; // "active"
     * echo $status['total_games']; // 8
     * echo $status['active_sessions']; // 3
     * ```
     */
    public function status(string $token): array
    {
        try {
            $response = $this->api->getMultiSessionStatus($token);

            return $this->formatStatusResponse($token, $response);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'token' => $token,
                'status' => null,
            ];
        }
    }

    /**
     * End a multi-session and close all active game sessions
     *
     * @param string $token Multi-session token/ID
     * @return array Result
     *
     * @example
     * ```php
     * $result = $client->multiSession()->end($multiSessionId);
     * ```
     */
    public function end(string $token): array
    {
        try {
            $response = $this->api->endMultiSession($token);

            return [
                'success' => true,
                'token' => $token,
                'message' => 'Multi-session ended successfully',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'token' => $token,
            ];
        }
    }

    /**
     * Generate an iframe for embedding the swipe UI
     *
     * @param string $swipeUrl The swipe URL from start()
     * @param array $options Iframe options:
     *   - width: Width (default: '100%')
     *   - height: Height (default: '100%')
     *   - id: Element ID
     *   - class: CSS class
     *   - allow: Allow attributes (default: fullscreen, autoplay)
     * @return string HTML iframe element
     */
    public function getIframe(string $swipeUrl, array $options = []): string
    {
        $width = $options['width'] ?? '100%';
        $height = $options['height'] ?? '100%';
        $id = isset($options['id']) ? " id=\"{$options['id']}\"" : '';
        $class = isset($options['class']) ? " class=\"{$options['class']}\"" : '';
        $allow = $options['allow'] ?? 'fullscreen; autoplay; encrypted-media';

        return <<<HTML
<iframe{$id}{$class}
    src="{$swipeUrl}"
    width="{$width}"
    height="{$height}"
    frameborder="0"
    allow="{$allow}"
    allowfullscreen>
</iframe>
HTML;
    }

    /**
     * Format the start session response
     */
    protected function formatStartResponse(?StartAMultiSession201Response $response): array
    {
        if ($response === null) {
            return [
                'success' => false,
                'multi_session_id' => null,
                'swipe_url' => null,
            ];
        }

        $data = $response->getData();
        if ($data === null) {
            return [
                'success' => true,
                'multi_session_id' => null,
                'swipe_url' => null,
                'raw' => $this->modelToArray($response),
            ];
        }

        $games = [];
        if (method_exists($data, 'getGames') && $data->getGames() !== null) {
            foreach ($data->getGames() as $game) {
                $games[] = $this->modelToArray($game);
            }
        }

        return [
            'success' => true,
            'multi_session_id' => method_exists($data, 'getMultiSessionId') ? $data->getMultiSessionId() : null,
            'swipe_url' => method_exists($data, 'getSwipeUrl') ? $data->getSwipeUrl() : null,
            'total_games' => method_exists($data, 'getTotalGames') ? $data->getTotalGames() : count($games),
            'games' => $games,
            'expires_at' => method_exists($data, 'getExpiresAt') ? $data->getExpiresAt() : null,
            'raw' => $this->modelToArray($response),
        ];
    }

    /**
     * Format the status response
     */
    protected function formatStatusResponse(string $token, ?GetMultiSessionStatus200Response $response): array
    {
        if ($response === null) {
            return [
                'success' => false,
                'token' => $token,
                'status' => null,
            ];
        }

        $data = $response->getData();
        if ($data === null) {
            return [
                'success' => true,
                'token' => $token,
                'status' => null,
                'raw' => $this->modelToArray($response),
            ];
        }

        $games = [];
        if (method_exists($data, 'getGames') && $data->getGames() !== null) {
            foreach ($data->getGames() as $game) {
                $games[] = $this->modelToArray($game);
            }
        }

        return [
            'success' => true,
            'token' => $token,
            'status' => method_exists($data, 'getStatus') ? $data->getStatus() : null,
            'total_games' => method_exists($data, 'getTotalGames') ? $data->getTotalGames() : count($games),
            'active_sessions' => method_exists($data, 'getActiveSessions') ? $data->getActiveSessions() : 0,
            'current_index' => method_exists($data, 'getCurrentIndex') ? $data->getCurrentIndex() : 0,
            'games' => $games,
            'expires_at' => method_exists($data, 'getExpiresAt') ? $data->getExpiresAt() : null,
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
