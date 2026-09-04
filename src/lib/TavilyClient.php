<?php

namespace App\Lib;

use GuzzleHttp\Client;

class TavilyClient
{
    private $client;
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.tavily.com/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function search(string $query, int $limit = 5, array $excludeDomains = [])
    {
        try {
            $response = $this->client->post('search', [
                'json' => [
                    'api_key'             => $this->apiKey,
                    'query'               => $query,
                    'search_depth'        => 'advanced',
                    'include_images'      => false,
                    'include_raw_content' => true,
                    'max_results'         => $limit,
                    'exclude_domains'     => $excludeDomains
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            error_log("Tavily API Error: " . $e->getMessage());
            return null;
        }
    }
}
