<?php

namespace common\helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use yii\web\UnprocessableEntityHttpException;

class AdvancedOpenAIImage
{
    private $client;
    private $apiKey;

    // 支持的图片尺寸
    const SIZES = [
        'small' => '256x256',
        'medium' => '512x512',
        'large' => '1024x1024',
        'hd' => '1024x1024',
        'square' => '1024x1024'
    ];

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30.0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * 生成图片并返回URL
     */
    public function generateImageUrl($prompt, $size = 'large', $num = 1)
    {
        return $this->generateImage($prompt, $size, $num, 'url');
    }

    /**
     * 生成图片并返回Base64数据
     */
    public function generateImageBase64($prompt, $size = 'large', $num = 1)
    {
        return $this->generateImage($prompt, $size, $num, 'b64_json');
    }

    /**
     * 生成图片核心方法
     */
    private function generateImage($prompt, $size, $num, $format)
    {
        // 验证尺寸
        if (!isset(self::SIZES[$size])) {
            throw new UnprocessableEntityHttpException('不支持的图片尺寸');
        }

        $actualSize = self::SIZES[$size];

        try {
            $response = $this->client->post('images/generations', [
                'json' => [
                    'prompt' => $prompt,
                    'n' => $num,
                    'size' => $actualSize,
                    'response_format' => $format
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new UnprocessableEntityHttpException('API调用失败: ' . $e->getMessage());
        }
    }

    /**
     * 下载图片到本地
     */
    public function downloadImage($imageUrl, $savePath)
    {
        try {
            $imageData = file_get_contents($imageUrl);
            file_put_contents($savePath, $imageData);
            return true;
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException('图片下载失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取信息
     * @param $keyword
     * @return mixed|void
     */
    public static function get($keyword)
    {
        try {
            $apiKey = '';
            $imageAI = new AdvancedOpenAIImage($apiKey);

            // 生成图片URL
            $result = $imageAI->generateImageUrl(
                $keyword,
                'large',
                1
            );

            $imageUrl = $result['data'][0]['url'];
            return $imageUrl;

            // 下载图片
            // $imageAI->downloadImage($imageUrl, 'my_image.png');

        } catch (\Exception $e) {
            echo "错误: " . $e->getMessage();
            exit;
        }
    }
}
?>