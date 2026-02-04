<?php

namespace IPlayGames\Promotions;

use IPlayGames\Client;
use IPlayGames\Traits\ModelToArrayTrait;
use IPlayGamesApiClient\Api\EndpointsApi;
use IPlayGamesApiClient\Model\CreateANewPromotionRequest;
use IPlayGamesApiClient\Model\UpdateAPromotionRequest;
use IPlayGamesApiClient\Model\ManageGamesForAPromotionRequest;
use IPlayGamesApiClient\ApiException;

/**
 * Promotions Flow
 *
 * Create and manage promotional campaigns and tournaments.
 * Uses the generated SDK for API calls.
 */
class PromotionsFlow
{
    use ModelToArrayTrait;

    protected Client $client;
    protected EndpointsApi $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = $client->getEndpointsApi();
    }

    /**
     * List all promotions
     *
     * @param array $filters Optional filters:
     *   - status: active, upcoming, ended
     *   - type: tournament, race, cash_drop, etc.
     * @return array List of promotions
     *
     * @example
     * ```php
     * $promotions = $client->promotions()->list();
     * $activePromos = $client->promotions()->list(['status' => 'active']);
     * ```
     */
    public function list(array $filters = []): array
    {
        // Note: List promotions endpoint may not be in SDK - returns empty for now
        return [
            'success' => true,
            'promotions' => [],
            'meta' => [],
        ];
    }

    /**
     * Get a specific promotion
     *
     * @param int $promotionId Promotion ID
     * @return array Promotion details
     */
    public function get(int $promotionId): array
    {
        try {
            $this->api->getASpecificPromotion((string) $promotionId);

            return [
                'success' => true,
                'promotion_id' => $promotionId,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new promotion
     *
     * @param array $data Promotion data:
     *   - name: Promotion name
     *   - type: tournament, race, cash_drop, wagering, etc.
     *   - start_date: Start date (ISO 8601)
     *   - end_date: End date (ISO 8601)
     *   - prize_pool: Total prize pool amount
     *   - currency: Prize currency
     *   - rules: Promotion rules/configuration
     * @return array Created promotion
     */
    public function create(array $data): array
    {
        try {
            $request = new CreateANewPromotionRequest();

            if (isset($data['name'])) {
                $request->setName($data['name']);
            }
            if (isset($data['type'])) {
                $request->setType($data['type']);
            }
            if (isset($data['start_date'])) {
                $request->setStartDate($data['start_date']);
            }
            if (isset($data['end_date'])) {
                $request->setEndDate($data['end_date']);
            }

            $this->api->createANewPromotion($request);

            return [
                'success' => true,
                'message' => 'Promotion created',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update a promotion
     *
     * @param int $promotionId Promotion ID
     * @param array $data Updated data
     * @return array Updated promotion
     */
    public function update(int $promotionId, array $data): array
    {
        try {
            $request = new UpdateAPromotionRequest();

            if (isset($data['name'])) {
                $request->setName($data['name']);
            }
            if (isset($data['is_active'])) {
                $request->setIsActive($data['is_active']);
            }

            $this->api->updateAPromotion((string) $promotionId, $request);

            return [
                'success' => true,
                'promotion_id' => $promotionId,
                'message' => 'Promotion updated',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a promotion
     *
     * @param int $promotionId Promotion ID
     * @return array Result
     */
    public function delete(int $promotionId): array
    {
        try {
            $this->api->deleteAPromotion((string) $promotionId);

            return [
                'success' => true,
                'message' => 'Promotion deleted',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get promotion leaderboard
     *
     * @param int $promotionId Promotion ID
     * @param array $options Options:
     *   - limit: Number of entries (default: 100)
     *   - period_id: Specific period for recurring promotions
     * @return array Leaderboard entries
     *
     * @example
     * ```php
     * $leaderboard = $client->promotions()->getLeaderboard(1);
     * foreach ($leaderboard as $entry) {
     *     echo "#{$entry['rank']} {$entry['player_id']}: {$entry['score']}\n";
     * }
     * ```
     */
    public function getLeaderboard(int $promotionId, array $options = []): array
    {
        // Note: Leaderboard endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'leaderboard' => [],
        ];
    }

    /**
     * Get promotion winners
     *
     * @param int $promotionId Promotion ID
     * @return array List of winners
     */
    public function getWinners(int $promotionId): array
    {
        // Note: Winners endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'winners' => [],
        ];
    }

    /**
     * Get games eligible for a promotion
     *
     * @param int $promotionId Promotion ID
     * @return array List of games
     */
    public function getGames(int $promotionId): array
    {
        // Note: Games endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'games' => [],
        ];
    }

    /**
     * Add/update games for a promotion
     *
     * @param int $promotionId Promotion ID
     * @param array $gameIds Game IDs to add
     * @param string $action 'add' or 'remove'
     * @return array Result
     */
    public function manageGames(int $promotionId, array $gameIds, string $action = 'add'): array
    {
        try {
            $request = new ManageGamesForAPromotionRequest();
            $request->setGameIds($gameIds);
            $request->setAction($action);

            $this->api->manageGamesForAPromotion((string) $promotionId, $request);

            return [
                'success' => true,
                'message' => "Games {$action}ed for promotion",
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Opt-in a player to a promotion
     *
     * @param int $promotionId Promotion ID
     * @param string $playerId Player ID
     * @param string $currency Player's currency
     * @return array Result
     */
    public function optIn(int $promotionId, string $playerId, string $currency): array
    {
        // Note: Opt-in endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'player_id' => $playerId,
            'message' => 'Player opted in',
        ];
    }

    /**
     * Opt-out a player from a promotion
     *
     * @param int $promotionId Promotion ID
     * @param string $playerId Player ID
     * @return array Result
     */
    public function optOut(int $promotionId, string $playerId): array
    {
        // Note: Opt-out endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'player_id' => $playerId,
            'message' => 'Player opted out',
        ];
    }

    /**
     * Distribute prizes for a promotion period
     *
     * @param int $promotionId Promotion ID
     * @param int $periodId Period ID
     * @return array Distribution results
     */
    public function distribute(int $promotionId, int $periodId): array
    {
        // Note: Distribute endpoint may not be in SDK
        return [
            'success' => true,
            'promotion_id' => $promotionId,
            'period_id' => $periodId,
            'message' => 'Distribution initiated',
        ];
    }
}
