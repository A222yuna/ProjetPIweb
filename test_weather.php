<?php
$ch = curl_init("https://api.openweathermap.org/data/2.5/weather?q=Tunis&appid=a5bd0a8d55cf9a3f651a166b1a8723be&units=metric&lang=fr");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
if ($response === false) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo 'Response received successfully';
}
curl_close($ch);
