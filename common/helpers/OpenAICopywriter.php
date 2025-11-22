<?php

namespace common\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAICopywriter
{
    private $client;
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30.0,
        ]);
        $this->apiKey = $apiKey;
    }

//    public function generateMarketingCopy($product, $features, $style = '专业') {
    public function generateMarketingCopy($keyword)
    {
//        $prompt = "为{$product}创作一段营销文案，主要特点：{$features}。风格：{$style}，字数300字左右";
        $prompt = "{$keyword}。风格：专业，字数300字左右";

        try {
            $response = $this->client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => '你是资深营销文案专家，擅长创作吸引人的产品描述和广告文案'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 800,
                    'temperature' => 0.7,
                    'top_p' => 0.9
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['choices'][0]['message']['content'];

        } catch (RequestException $e) {
            return '请求失败：' . $e->getMessage();
        }
    }

    // 生成广告标语
    public function generateSlogan($product, $targetAudience)
    {
        $prompt = "为{$product}生成5个吸引{$targetAudience}的广告标语，每个不超过15个字";

        $response = $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.8
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['message']['content'];
    }

    public static function get($keyword)
    {
        $apiKey = '';
        $copywriter = new OpenAICopywriter($apiKey);

// 生成产品描述
        $productDesc = $copywriter->generateMarketingCopy(
            $keyword
        );
        return $productDesc;
    }
}

// 使用示例
//$apiKey = '你的API密钥';
//$copywriter = new OpenAICopywriter($apiKey);
//
//// 生成产品描述
//$productDesc = $copywriter->generateMarketingCopy(
//    '无线蓝牙耳机',
//    '降噪功能、30小时续航、防水防汗',
//    '科技感'
//);
//echo $productDesc;
//
//// 生成广告标语
//$slogans = $copywriter->generateSlogan('健身APP', '年轻人');
//echo $slogans;
?>