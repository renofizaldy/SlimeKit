<?php

namespace App\Lib;

use GuzzleHttp\Client;

class OpenAIClient
{
    private $client;
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }

    public function generateContent(string $systemPrompt, string $userPrompt, string $model = 'gpt-4o', array $options = [])
    {
        try {
            $json = array_merge([
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ]
            ], $options);
            $response = $this->client->post('chat/completions', ['json' => $json]);

            $body = json_decode($response->getBody(), true);
            if (isset($body['choices'][0]['message']['content'])) {
                return $body['choices'][0]['message']['content'];
            }

            return null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
