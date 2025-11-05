<?php
$apiKey = 'your_openai_api_key';
$endpoint = 'https://api.openai.com/v1/images/generations';

$data = [
    'prompt' => 'A beautiful sunset over mountains', // 你的生成描述
    'n' => 1,
    'size' => '1024x1024'
];

$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['data'][0]['url'])) {
    echo 'Generated Image URL: ' . $result['data'][0]['url'];
} else {
    echo 'Error: ' . $response;
}
?>
