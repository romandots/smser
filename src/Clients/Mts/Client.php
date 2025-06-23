<?php

namespace Romandots\Smser\Clients\Mts;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Romandots\Smser\Exceptions\InvalidConfiguration;
use Romandots\Smser\Exceptions\ServiceUnavailable;

class Client
{
    private const DEFAULT_BASE_URI = 'https://api.exolve.ru';

    private HttpClient $http;
    private string $baseUri;
    private string $token;
    private string $sender;

    public function __construct(array $config)
    {
        $this->validateConfig($config);

        $this->baseUri = rtrim($config['base_uri'] ?? self::DEFAULT_BASE_URI, '/');
        $this->token = $config['token'];
        $this->sender = $config['sender'];

        $this->http = new HttpClient([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param array $config
     * @return void
     * @throws InvalidConfiguration
     */
    private function validateConfig(array $config): void
    {
        foreach (['token', 'sender'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
                throw new InvalidConfiguration("Configuration option '{$key}' is required");
            }
        }

        if (isset($config['base_uri']) && (!is_string($config['base_uri']) || $config['base_uri'] === '')) {
            throw new InvalidConfiguration("Configuration option 'base_uri' must be a non-empty string");
        }
    }

    /**
     * Send SMS using MTS API
     *
     * @throws ServiceUnavailable
     */
    public function sendSms(string $destination, string $text): array
    {
        try {
            $response = $this->http->post('/messaging/v1/SendSMS', [
                'json' => [
                    'number' => $this->sender,
                    'destination' => $destination,
                    'text' => $text,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            $status = $e instanceof RequestException && $e->hasResponse()
                ? $e->getResponse()?->getStatusCode()
                : null;

            throw new ServiceUnavailable('Failed to send SMS', 'MTS API', $status, $e);
        }
    }

    /**
     * Retrieve account balance
     *
     * @throws ServiceUnavailable
     */
    public function getBalance(): float
    {
        try {
            $response = $this->http->post('/finance/v1/GetBalance', [
                'json' => new \stdClass(),
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return isset($data['balance']) ? (float)$data['balance'] : 0.0;
        } catch (GuzzleException $e) {
            $status = $e instanceof RequestException && $e->hasResponse()
                ? $e->getResponse()?->getStatusCode()
                : null;

            throw new ServiceUnavailable('Failed to retrieve balance', 'MTS API', $status, $e);
        }
    }
}