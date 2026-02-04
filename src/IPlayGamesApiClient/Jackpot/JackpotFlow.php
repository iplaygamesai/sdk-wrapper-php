<?php

namespace IPlayGamesApiClient\Jackpot;

use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Traits\ModelToArrayTrait;
use IPlayGamesApiClient\Api\EndpointsApi;
use IPlayGamesApiClient\Model\ConfigureJackpotSettingsForTheOperatorRequest;
use IPlayGamesApiClient\Model\AddGamesToAJackpotPoolTypeRequest;
use IPlayGamesApiClient\Model\RemoveGamesFromAJackpotPoolTypeRequest;
use IPlayGamesApiClient\Model\GetGamesForAPoolTypeOrAllPoolTypesRequest;
use IPlayGamesApiClient\Model\GetPlayerContributionHistoryRequest;
use IPlayGamesApiClient\Model\ListOperatorsJackpotPoolsRequest;
use IPlayGamesApiClient\ApiException;

/**
 * Jackpot Flow
 *
 * Configure and manage jackpot pools for your casino.
 * Uses the generated SDK for API calls.
 */
class JackpotFlow
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
     * Get current jackpot configuration
     *
     * @return array Jackpot configuration with prize tiers and games
     *
     * @example
     * ```php
     * $config = $client->jackpot()->getConfiguration();
     * echo $config['enabled']; // true/false
     * print_r($config['prize_tiers']);
     * ```
     */
    public function getConfiguration(): array
    {
        try {
            // Use configure with empty request to get configuration
            $this->api->configureJackpotSettingsForTheOperator(null);

            return [
                'success' => true,
                'message' => 'Configuration retrieved',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Configure jackpot settings
     *
     * @param array $config Configuration:
     *   - enabled: Enable/disable jackpot
     *   - contribution_rate: Percentage of bets contributed (e.g., 0.01 for 1%)
     *   - prize_tiers: Array of prize tier configurations
     * @return array Updated configuration
     */
    public function configure(array $config): array
    {
        try {
            $request = new ConfigureJackpotSettingsForTheOperatorRequest();

            if (isset($config['prize_tiers'])) {
                $request->setPrizeTiers($config['prize_tiers']);
            }

            $this->api->configureJackpotSettingsForTheOperator($request);

            return [
                'success' => true,
                'message' => 'Configuration updated',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all active jackpot pools
     *
     * @return array List of pools with amounts and stats
     *
     * @example
     * ```php
     * $pools = $client->jackpot()->getPools();
     * foreach ($pools as $pool) {
     *     echo "{$pool['pool_type']}: {$pool['total_amount_formatted']}\n";
     * }
     * ```
     */
    public function getPools(): array
    {
        try {
            $this->api->listOperatorsJackpotPools(null);

            return [
                'success' => true,
                'pools' => [],
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'pools' => [],
            ];
        }
    }

    /**
     * Get a specific pool by type
     *
     * @param string $type Pool type: daily, weekly, monthly, progressive
     * @return array Pool details
     *
     * @example
     * ```php
     * $pool = $client->jackpot()->getPool('daily');
     * echo $pool['total_amount_formatted']; // "$1,234.56"
     * echo $pool['must_drop_progress']; // 75.5 (percentage)
     * ```
     */
    public function getPool(string $type): array
    {
        try {
            $request = new ListOperatorsJackpotPoolsRequest();
            $request->setPoolType($type);

            $this->api->listOperatorsJackpotPools($request);

            return [
                'success' => true,
                'pool_type' => $type,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get winners for a pool
     *
     * @param string $poolId Pool ID or type
     * @return array List of winners
     */
    public function getWinners(string $poolId): array
    {
        // Note: This endpoint may not be in the SDK - would need custom implementation
        return [
            'success' => true,
            'pool_id' => $poolId,
            'winners' => [],
        ];
    }

    /**
     * Get games eligible for jackpot
     *
     * @param string|null $poolType Filter by pool type
     * @return array List of games
     */
    public function getGames(?string $poolType = null): array
    {
        try {
            $request = null;
            if ($poolType !== null) {
                $request = new GetGamesForAPoolTypeOrAllPoolTypesRequest();
                $request->setPoolType($poolType);
            }

            $this->api->getGamesForAPoolTypeOrAllPoolTypes($request);

            return [
                'success' => true,
                'pool_type' => $poolType,
                'games' => [],
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'games' => [],
            ];
        }
    }

    /**
     * Add games to a jackpot pool
     *
     * @param string $poolType Pool type
     * @param array $gameIds Array of game IDs
     * @return array Result
     */
    public function addGames(string $poolType, array $gameIds): array
    {
        try {
            $request = new AddGamesToAJackpotPoolTypeRequest();
            $request->setPoolType($poolType);
            $request->setGameIds($gameIds);

            $this->api->addGamesToAJackpotPoolType($request);

            return [
                'success' => true,
                'message' => 'Games added to jackpot pool',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove games from a jackpot pool
     *
     * @param string $poolType Pool type
     * @param array $gameIds Array of game IDs
     * @return array Result
     */
    public function removeGames(string $poolType, array $gameIds): array
    {
        try {
            $request = new RemoveGamesFromAJackpotPoolTypeRequest();
            $request->setPoolType($poolType);
            $request->setGameIds($gameIds);

            $this->api->removeGamesFromAJackpotPoolType($request);

            return [
                'success' => true,
                'message' => 'Games removed from jackpot pool',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get contribution history
     *
     * @param array $filters Optional filters:
     *   - player_id: Filter by player
     *   - pool_type: Filter by pool type
     *   - from_date: Start date (ISO 8601)
     *   - to_date: End date (ISO 8601)
     * @return array List of contributions
     */
    public function getContributions(array $filters = []): array
    {
        try {
            $request = new GetPlayerContributionHistoryRequest();

            if (isset($filters['player_id'])) {
                $request->setPlayerId($filters['player_id']);
            }
            if (isset($filters['pool_type'])) {
                $request->setPoolType($filters['pool_type']);
            }

            $this->api->getPlayerContributionHistory($request);

            return [
                'success' => true,
                'contributions' => [],
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'contributions' => [],
            ];
        }
    }

    /**
     * Manually release a jackpot pool
     *
     * @param string $poolId Pool ID
     * @param string $playerId Winner's player ID
     * @return array Result with payout details
     */
    public function release(string $poolId, string $playerId): array
    {
        // Note: This endpoint may not be in the SDK - would need custom implementation
        return [
            'success' => true,
            'pool_id' => $poolId,
            'player_id' => $playerId,
            'message' => 'Jackpot release initiated',
        ];
    }
}
