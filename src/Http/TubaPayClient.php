<?php

declare(strict_types=1);

namespace Core45\TubaPay\Http;

use Core45\TubaPay\Enum\Environment;
use Core45\TubaPay\Exception\ApiException;
use Core45\TubaPay\Exception\AuthenticationException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * HTTP client for making authenticated requests to TubaPay API.
 */
final class TubaPayClient
{
    private readonly ClientInterface $httpClient;

    private readonly TokenManager $tokenManager;

    private readonly Environment $environment;

    private readonly ?LoggerInterface $logger;

    /**
     * @param  array<string, mixed>  $guzzleOptions  Additional Guzzle options
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        Environment $environment = Environment::Test,
        ?TokenStorageInterface $tokenStorage = null,
        ?ClientInterface $httpClient = null,
        array $guzzleOptions = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->environment = $environment;
        $this->logger = $logger;

        $defaultOptions = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => true,
        ];

        if ($httpClient !== null) {
            $this->httpClient = $httpClient;
        } else {
            $stack = HandlerStack::create();

            if ($logger !== null) {
                $stack->push($this->createLoggingMiddleware($logger), 'tubapay_logger');
            }

            $this->httpClient = new Client(array_merge($defaultOptions, $guzzleOptions, [
                'handler' => $stack,
            ]));
        }

        $this->tokenManager = new TokenManager(
            $this->httpClient,
            $clientId,
            $clientSecret,
            $environment,
            $tokenStorage ?? new InMemoryTokenStorage,
            $logger,
        );
    }

    /**
     * Create Guzzle logging middleware for detailed request/response logging.
     */
    private function createLoggingMiddleware(LoggerInterface $logger): callable
    {
        return Middleware::log(
            $logger,
            new MessageFormatter(
                "TubaPay API Request:\n".
                ">>>>>>>>\n".
                "{method} {uri}\n".
                "Headers: {req_headers}\n".
                "Body: {req_body}\n".
                "<<<<<<<<\n".
                "Response: {code} {phrase}\n".
                "Headers: {res_headers}\n".
                "Body: {res_body}\n".
                'Time: {total_time}s'
            ),
            LogLevel::DEBUG
        );
    }

    /**
     * Make an authenticated GET request.
     *
     * @param  array<string, mixed>  $query  Query parameters
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * Make an authenticated POST request.
     *
     * @param  array<string, mixed>  $data  Request body
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    /**
     * Make an authenticated PUT request.
     *
     * @param  array<string, mixed>  $data  Request body
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function put(string $path, array $data = []): array
    {
        return $this->request('PUT', $path, ['json' => $data]);
    }

    /**
     * Make an authenticated DELETE request.
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    /**
     * Make an authenticated request to the TubaPay API.
     *
     * @param  array<string, mixed>  $options  Guzzle request options
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $url = $this->buildUrl($path);
        $token = $this->tokenManager->getAccessToken();

        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return [];
            }

            return $data;
        } catch (ConnectException $e) {
            throw ApiException::connectionError($e->getMessage());
        } catch (RequestException $e) {
            return $this->handleRequestException($e, $path);
        } catch (GuzzleException $e) {
            throw ApiException::connectionError($e->getMessage());
        }
    }

    /**
     * Get the token manager for direct token operations.
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    /**
     * Get the current environment.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Build the full URL for an API path.
     */
    public function buildUrl(string $path): string
    {
        $baseUrl = $this->environment->getBaseUrl();
        $path = ltrim($path, '/');

        return $baseUrl.'/'.$path;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    private function handleRequestException(RequestException $e, string $path): array
    {
        $response = $e->getResponse();

        if ($response === null) {
            $this->logger?->error('TubaPay: API request failed - no response', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw ApiException::connectionError($e->getMessage());
        }

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $responseData = is_array($data) ? $data : [];

        $this->logger?->error('TubaPay: API request failed', [
            'path' => $path,
            'status_code' => $statusCode,
            'response_body' => $body,
            'response_data' => $responseData,
        ]);

        // Handle authentication errors
        if ($statusCode === 401) {
            $this->tokenManager->clearToken();
            throw AuthenticationException::tokenExpired();
        }

        throw ApiException::fromResponse($responseData, $statusCode, $path);
    }
}
