<?php
$apiKey = 'AIzaSyCj3vn_lxOR6t_rYT4H4LJ4ARsUBb6LP2s';
$model = 'gemini-2.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

$data = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => 'Hello']
            ]
        ]
    ]
]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true // To get the response body even on 4xx/5xx
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "HTTP response header:\n";
var_dump($http_response_header[0]);
echo "Response body:\n";
echo $result;
