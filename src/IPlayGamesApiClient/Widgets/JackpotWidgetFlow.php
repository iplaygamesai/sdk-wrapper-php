<?php

namespace IPlayGamesApiClient\Widgets;

use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Traits\ModelToArrayTrait;
use IPlayGamesApiClient\Api\WidgetManagementApi;
use IPlayGamesApiClient\Model\RegisterANewDomainRequest;
use IPlayGamesApiClient\Model\UpdateDomainSettingsRequest;
use IPlayGamesApiClient\Model\GenerateAWidgetTokenRequest;
use IPlayGamesApiClient\Model\BulkRevokeTokensRequest;
use IPlayGamesApiClient\ApiException;

/**
 * Jackpot Widget Flow
 *
 * Manage widget tokens and domains for embedding jackpot displays on your website.
 * Uses the generated SDK for API calls.
 *
 * Flow:
 * 1. Register your domain(s) where the widget will be embedded
 * 2. Create widget tokens (anonymous or player-specific)
 * 3. Use the token to authenticate widget API calls
 */
class JackpotWidgetFlow
{
    use ModelToArrayTrait;

    protected Client $client;
    protected WidgetManagementApi $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = $client->getWidgetManagementApi();
    }

    /**
     * Register a domain for widget embedding
     *
     * @param string $domain Domain name (e.g., 'casino.example.com')
     * @param array $options Optional settings:
     *   - name: Friendly name for the domain
     *   - allowed_pool_types: Array of allowed pool types (default: all)
     * @return array Domain data including domain_token
     *
     * @example
     * ```php
     * $domain = $client->jackpotWidget()->registerDomain('casino.example.com');
     * $domainToken = $domain['domain_token']; // Use this to create widget tokens
     * ```
     */
    public function registerDomain(string $domain, array $options = []): array
    {
        try {
            $request = new RegisterANewDomainRequest();
            $request->setDomain($domain);

            if (isset($options['name'])) {
                $request->setName($options['name']);
            }

            $response = $this->api->registerANewDomain($request);
            $data = $response->getData();

            return [
                'success' => true,
                'domain' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List registered domains
     *
     * @return array List of domains
     */
    public function listDomains(): array
    {
        try {
            $response = $this->api->listRegisteredDomains();
            $data = $response->getData();

            $domains = [];
            if ($data !== null) {
                foreach ($data as $domain) {
                    $domains[] = $this->modelToArray($domain);
                }
            }

            return [
                'success' => true,
                'domains' => $domains,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'domains' => [],
            ];
        }
    }

    /**
     * Get domain details
     *
     * @param int $domainId Domain ID
     * @return array Domain details
     */
    public function getDomain(int $domainId): array
    {
        try {
            $response = $this->api->getDomainDetails((string) $domainId);
            $data = $response->getData();

            return [
                'success' => true,
                'domain' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update domain settings
     *
     * @param int $domainId Domain ID
     * @param array $settings Updated settings
     * @return array Updated domain
     */
    public function updateDomain(int $domainId, array $settings): array
    {
        try {
            $request = new UpdateDomainSettingsRequest();

            if (isset($settings['name'])) {
                $request->setName($settings['name']);
            }
            if (isset($settings['is_active'])) {
                $request->setIsActive($settings['is_active']);
            }

            $response = $this->api->updateDomainSettings((string) $domainId, $request);
            $data = $response->getData();

            return [
                'success' => true,
                'domain' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a domain (revokes all associated tokens)
     *
     * @param int $domainId Domain ID
     * @return array Result
     */
    public function deleteDomain(int $domainId): array
    {
        try {
            $this->api->removeADomain((string) $domainId);

            return [
                'success' => true,
                'message' => 'Domain deleted successfully',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Regenerate domain token (revokes all existing widget tokens)
     *
     * @param int $domainId Domain ID
     * @return array New domain data with new domain_token
     */
    public function regenerateDomainToken(int $domainId): array
    {
        try {
            $response = $this->api->regenerateDomainToken((string) $domainId);
            $data = $response->getData();

            return [
                'success' => true,
                'domain' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a widget token
     *
     * @param string $domainToken Domain token from registerDomain()
     * @param array $options Optional settings:
     *   - player_id: Lock token to specific player (requires currency)
     *   - currency: Player's currency (required if player_id is set)
     *   - expires_at: Token expiration (ISO 8601)
     *   - allowed_pool_types: Restrict to specific pool types
     *   - config: Additional widget configuration
     * @return array Token data (full token only visible at creation)
     *
     * @example
     * ```php
     * // Anonymous token (view-only)
     * $token = $client->jackpotWidget()->createToken($domainToken);
     *
     * // Player-specific token (can start game sessions)
     * $token = $client->jackpotWidget()->createToken($domainToken, [
     *     'player_id' => 'player_456',
     *     'currency' => 'USD',
     * ]);
     *
     * // Use the token in your frontend
     * $widgetToken = $token['token'];
     * ```
     */
    public function createToken(string $domainToken, array $options = []): array
    {
        try {
            $request = new GenerateAWidgetTokenRequest();
            $request->setDomainToken($domainToken);

            if (isset($options['player_id'])) {
                $request->setPlayerId($options['player_id']);
            }
            if (isset($options['currency'])) {
                $request->setCurrency($options['currency']);
            }

            $response = $this->api->generateAWidgetToken($request);
            $data = $response->getData();

            return [
                'success' => true,
                'token' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an anonymous widget token (view-only, no player context)
     *
     * @param string $domainToken Domain token
     * @param array $options Additional options
     * @return array Token data
     */
    public function createAnonymousToken(string $domainToken, array $options = []): array
    {
        return $this->createToken($domainToken, $options);
    }

    /**
     * Create a player-specific widget token
     *
     * @param string $domainToken Domain token
     * @param string $playerId Player ID
     * @param string $currency Player's currency
     * @param array $options Additional options
     * @return array Token data
     */
    public function createPlayerToken(string $domainToken, string $playerId, string $currency, array $options = []): array
    {
        return $this->createToken($domainToken, array_merge($options, [
            'player_id' => $playerId,
            'currency' => $currency,
        ]));
    }

    /**
     * List all widget tokens
     *
     * @param array $filters Optional filters:
     *   - domain_id: Filter by domain
     *   - active: Filter by active status
     * @return array List of tokens
     */
    public function listTokens(array $filters = []): array
    {
        try {
            $response = $this->api->listWidgetTokens(
                $filters['domain_id'] ?? null,
                $filters['active'] ?? null
            );
            $data = $response->getData();

            $tokens = [];
            if ($data !== null) {
                foreach ($data as $token) {
                    $tokens[] = $this->modelToArray($token);
                }
            }

            return [
                'success' => true,
                'tokens' => $tokens,
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tokens' => [],
            ];
        }
    }

    /**
     * Get token details
     *
     * @param int $tokenId Token ID
     * @return array Token details (token value not included)
     */
    public function getToken(int $tokenId): array
    {
        try {
            $response = $this->api->getTokenDetails((string) $tokenId);
            $data = $response->getData();

            return [
                'success' => true,
                'token' => $this->modelToArray($data),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke a widget token
     *
     * @param int $tokenId Token ID
     * @return array Result
     */
    public function revokeToken(int $tokenId): array
    {
        try {
            $this->api->revokeAToken((string) $tokenId);

            return [
                'success' => true,
                'message' => 'Token revoked successfully',
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk revoke widget tokens
     *
     * @param array $tokenIds Array of token IDs
     * @return array Result with count of revoked tokens
     */
    public function bulkRevokeTokens(array $tokenIds): array
    {
        try {
            $request = new BulkRevokeTokensRequest();
            $request->setTokenIds($tokenIds);

            $response = $this->api->bulkRevokeTokens($request);

            return [
                'success' => true,
                'result' => $this->modelToArray($response),
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate the JavaScript snippet for embedding the widget
     *
     * @param string $token Widget token
     * @param array $options Widget options:
     *   - container: Container element ID (default: 'iplaygames-jackpot-widget')
     *   - theme: Widget theme (light, dark)
     *   - pool_types: Array of pool types to display
     * @return string JavaScript snippet
     */
    public function getEmbedCode(string $token, array $options = []): string
    {
        $baseUrl = $this->client->getBaseUrl();
        $containerId = $options['container'] ?? 'iplaygames-jackpot-widget';
        $config = json_encode(array_merge($options, ['token' => $token]));

        return <<<HTML
<div id="{$containerId}"></div>
<script src="{$baseUrl}/widgets/jackpot.js"></script>
<script>
    IPlayGamesJackpotWidget.init({$config});
</script>
HTML;
    }
}
