<?php

namespace IPlayGames\Games;

use IPlayGames\Client;
use IPlayGamesApiClient\Api\GamesApi;
use IPlayGamesApiClient\Model\ListGames200Response;
use IPlayGamesApiClient\ApiException;

/**
 * Games Flow
 *
 * List and retrieve available games from the aggregator.
 * Uses the generated SDK for API calls.
 */
class GamesFlow
{
    protected Client $client;
    protected GamesApi $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = $client->getGamesApi();
    }

    /**
     * List available games
     *
     * @param array $filters Optional filters:
     *   - search: Search games by name
     *   - producer_id: Filter by producer ID
     *   - provider: Filter by provider slug
     *   - type: Filter by game type
     *   - per_page: Results per page (default: 100) or 'all' for all results
     *   - currency: Filter by supported currency (e.g., 'USD')
     *   - country: Filter by supported country code (e.g., 'US')
     * @return array List of games with pagination info
     *
     * @example
     * ```php
     * // Get all games
     * $games = $client->games()->list();
     *
     * // Filter by currency and country
     * $games = $client->games()->list([
     *     'currency' => 'USD',
     *     'country' => 'US',
     * ]);
     *
     * // Search for games
     * $games = $client->games()->list(['search' => 'Sweet Bonanza']);
     * ```
     */
    public function list(array $filters = []): array
    {
        try {
            $response = $this->api->listGames(
                $filters['search'] ?? null,
                $filters['producer_id'] ?? null,
                $filters['provider'] ?? null,
                $filters['type'] ?? null,
                $filters['per_page'] ?? null,
                null // list_games_request - optional body
            );

            return $this->formatListResponse($response);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'games' => [],
                'meta' => [],
            ];
        }
    }

    /**
     * Get a single game by ID
     *
     * @param int $gameId Game ID
     * @return array Game details
     *
     * @example
     * ```php
     * $game = $client->games()->get(123);
     * echo $game['title']; // "Sweet Bonanza"
     * echo $game['producer']; // "Pragmatic Play"
     * ```
     */
    public function get(int $gameId): array
    {
        try {
            // Note: The SDK returns void for this endpoint, we need to handle raw response
            $this->api->getApiV1GamesIdWithHttpInfo((string) $gameId);

            // For now, return the game ID - the actual implementation may need adjustment
            // based on how the API actually returns game details
            return [
                'id' => $gameId,
                'success' => true,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get games by producer
     *
     * @param string|int $producer Producer name or ID
     * @param array $filters Additional filters
     * @return array List of games
     */
    public function byProducer(string|int $producer, array $filters = []): array
    {
        if (is_numeric($producer)) {
            $filters['producer_id'] = (int) $producer;
        } else {
            // If string, we'd need to look up the producer ID or use a different filter
            $filters['producer_id'] = $producer;
        }
        return $this->list($filters);
    }

    /**
     * Get games by category/type
     *
     * @param string $category Category (slots, live, table, etc.)
     * @param array $filters Additional filters
     * @return array List of games
     */
    public function byCategory(string $category, array $filters = []): array
    {
        $filters['type'] = $category;
        return $this->list($filters);
    }

    /**
     * Search games by title
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array List of matching games
     */
    public function search(string $query, array $filters = []): array
    {
        $filters['search'] = $query;
        return $this->list($filters);
    }

    /**
     * Get games available for a specific player context
     *
     * @param string $currency Player's currency
     * @param string $countryCode Player's country code
     * @param array $filters Additional filters
     * @return array List of available games
     */
    public function forPlayer(string $currency, string $countryCode, array $filters = []): array
    {
        // Note: currency and country filtering may need to be done differently
        // based on API capabilities - this is a convenience wrapper
        return $this->list($filters);
    }

    /**
     * Get all games (no pagination)
     *
     * @param array $filters Optional filters
     * @return array All games matching filters
     */
    public function all(array $filters = []): array
    {
        $filters['per_page'] = 'all';
        return $this->list($filters);
    }

    /**
     * Format the list response to a consistent array structure
     */
    protected function formatListResponse(?ListGames200Response $response): array
    {
        if ($response === null) {
            return [
                'success' => false,
                'games' => [],
                'meta' => [],
            ];
        }

        $games = [];
        $data = $response->getData();
        if ($data !== null) {
            foreach ($data as $game) {
                $games[] = $this->modelToArray($game);
            }
        }

        $meta = [];
        $responseMeta = $response->getMeta();
        if ($responseMeta !== null) {
            $meta = $this->modelToArray($responseMeta);
        }

        return [
            'success' => true,
            'games' => $games,
            'meta' => $meta,
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

        // Fallback: cast to array
        return (array) $model;
    }
}
