<?php

namespace IPlayGamesApiClient\Widgets;

use IPlayGamesApiClient\Client;
use IPlayGamesApiClient\Traits\ModelToArrayTrait;
use IPlayGamesApiClient\Api\WidgetManagementApi;
use IPlayGamesApiClient\Model\RegisterANewDomainRequest;
use IPlayGamesApiClient\Model\GenerateAWidgetTokenRequest;
use IPlayGamesApiClient\ApiException;

/**
 * Promotion Widget Flow
 *
 * Manage widget tokens and domains for embedding promotion displays on your website.
 * Uses the generated SDK for API calls.
 *
 * This is similar to JackpotWidgetFlow but for promotions/tournaments.
 */
class PromotionWidgetFlow
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
     * Uses the same domain registration as jackpot widgets.
     *
     * @param string $domain Domain name
     * @param array $options Optional settings
     * @return array Domain data including domain_token
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
     * Create a widget token for promotions
     *
     * @param string $domainToken Domain token
     * @param array $options Optional settings:
     *   - player_id: Lock to specific player
     *   - currency: Player's currency
     *   - promotion_ids: Restrict to specific promotions
     * @return array Token data
     *
     * @example
     * ```php
     * // Anonymous token
     * $token = $client->promotionWidget()->createToken($domainToken);
     *
     * // Player token
     * $token = $client->promotionWidget()->createPlayerToken(
     *     $domainToken,
     *     'player_456',
     *     'USD'
     * );
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
     * Create an anonymous widget token
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
     * @param array $filters Optional filters
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
     * Generate the JavaScript snippet for embedding the widget
     *
     * @param string $token Widget token
     * @param array $options Widget options
     * @return string JavaScript snippet
     */
    public function getEmbedCode(string $token, array $options = []): string
    {
        $baseUrl = $this->client->getBaseUrl();
        $containerId = $options['container'] ?? 'iplaygames-promotion-widget';
        $config = json_encode(array_merge($options, ['token' => $token]));

        return <<<HTML
<div id="{$containerId}"></div>
<script src="{$baseUrl}/widgets/promotions.js"></script>
<script>
    IPlayGamesPromotionWidget.init({$config});
</script>
HTML;
    }
}
