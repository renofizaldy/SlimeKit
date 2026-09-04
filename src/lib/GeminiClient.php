<?php

namespace App\Lib;

use GuzzleHttp\Client;

class GeminiClient
{
    private $client;
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function generateContent(string $prompt, string $model = 'gemini-flash-latest')
    {
        try {
            $response = $this->client->post("models/{$model}:generateContent?key={$this->apiKey}", [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $body['candidates'][0]['content']['parts'][0]['text'];
                return $this->parseJsonIfPossible($text);
            }

            return null;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function parseJsonIfPossible($text)
    {
        // specific cleanup for JSON output if needed
        $text = trim($text);
        if (strpos($text, '```json') === 0) {
            $text = str_replace(['```json', '```'], '', $text);
        }

        if (strpos($text, '```') === 0) {
            $text = str_replace('```', '', $text);
        }
        
        $decoded = json_decode($text, true);
        return $decoded ? $decoded : $text;
    }
}
