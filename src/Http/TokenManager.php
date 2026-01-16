<?php

declare(strict_types=1);

namespace Core45\TubaPay\Http;

use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\AuthenticationException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Manages OAuth2 token acquisition and refresh for TubaPay API.
 */
final class TokenManager
{
    private const TOKEN_ENDPOINT = '/api/v1/partner/auth/token';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly Environment $environment,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get a valid access token, refreshing if necessary.
     *
     * @throws AuthenticationException If authentication fails
     */
    public function getAccessToken(): string
    {
        if ($this->tokenStorage->hasValidToken()) {
            $token = $this->tokenStorage->getToken();
            if ($token !== null) {
                return $token;
            }
        }

        return $this->refreshToken();
    }

    /**
     * Force refresh the access token.
     *
     * @throws AuthenticationException If authentication fails
     */
    public function refreshToken(): string
    {
        $this->validateCredentials();

        $tokenUrl = $this->getTokenUrl();

        $this->logger?->debug('TubaPay: Requesting OAuth token', [
            'url' => $tokenUrl,
            'client_id' => substr($this->clientId, 0, 8).'...',
        ]);

        try {
            $response = $this->httpClient->request('POST', $tokenUrl, [
                'json' => [
                    'clientId' => $this->clientId,
                    'clientSecret' => $this->clientSecret,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                $this->logger?->error('TubaPay: Invalid token response format', [
                    'response_body' => $body,
                ]);
                throw AuthenticationException::invalidCredentials('Invalid response format from token endpoint');
            }

            $this->logger?->debug('TubaPay: OAuth token obtained successfully');

            return $this->parseTokenResponse($data);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        } catch (GuzzleException $e) {
            $this->logger?->error('TubaPay: Connection error during token request', [
                'error' => $e->getMessage(),
            ]);
            throw AuthenticationException::invalidCredentials(
                'Connection error while obtaining token: '.$e->getMessage()
            );
        }
    }

    /**
     * Clear the stored token.
     */
    public function clearToken(): void
    {
        $this->tokenStorage->clearToken();
    }

    /**
     * Check if a valid token exists in storage.
     */
    public function hasValidToken(): bool
    {
        return $this->tokenStorage->hasValidToken();
    }

    private function validateCredentials(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw AuthenticationException::missingCredentials();
        }
    }

    private function getTokenUrl(): string
    {
        return $this->environment->getBaseUrl().self::TOKEN_ENDPOINT;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthenticationException
     */
    private function parseTokenResponse(array $data): string
    {
        // Response structure: {"result":{"response":{"accessToken":"...","expiresIn":86400}}}
        $response = $data['result']['response'] ?? $data;

        $accessToken = $response['accessToken'] ?? null;
        $expiresIn = $response['expiresIn'] ?? 3600;

        if (!is_string($accessToken) || empty($accessToken)) {
            throw AuthenticationException::invalidCredentials('No access token in response');
        }

        $this->tokenStorage->setToken($accessToken, (int) $expiresIn);

        return $accessToken;
    }

    /**
     * @throws AuthenticationException
     */
    private function handleRequestException(RequestException $e): never
    {
        $response = $e->getResponse();

        if ($response === null) {
            $this->logger?->error('TubaPay: Token request failed - no response', [
                'error' => $e->getMessage(),
            ]);
            throw AuthenticationException::invalidCredentials(
                'Connection error: '.$e->getMessage()
            );
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $message = 'Authentication failed';
        if (is_array($data)) {
            $message = $data['result']['response']['message']
                ?? $data['error_description']
                ?? $data['message']
                ?? $message;
        }

        $this->logger?->error('TubaPay: Token request failed', [
            'status_code' => $statusCode,
            'response_body' => $body,
            'error_message' => $message,
        ]);

        if ($statusCode === 401) {
            throw AuthenticationException::invalidCredentials($message);
        }

        throw AuthenticationException::invalidCredentials(
            sprintf('%s (HTTP %d)', $message, $statusCode)
        );
    }
}
