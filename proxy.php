<?php
header("Content-Type: application/json");

$url = $_GET['url'] ?? '';
if (!$url) {
    http_response_code(400);
    echo json_encode(["erro" => "Falta parâmetro ?url="]);
    exit;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(["erro" => $error]);
} else {
    echo $response;
}
?>
