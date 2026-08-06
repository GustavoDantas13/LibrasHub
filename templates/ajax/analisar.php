<?php

header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

if (!isset($_FILES["mediaFile"])) {
    echo json_encode([
        "success" => false,
        "error" => "Nenhum arquivo recebido."
    ]);
    exit;
}

$tempDir = __DIR__ . "/temp/";

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

$postFields = [];

$total = count($_FILES["mediaFile"]["name"]);

for ($i = 0; $i < $total; $i++) {

    $tmp = $_FILES["mediaFile"]["tmp_name"][$i];
    $nome = basename($_FILES["mediaFile"]["name"][$i]);

    $destino = $tempDir . uniqid() . "_" . $nome;

    if (!move_uploaded_file($tmp, $destino)) {

        echo json_encode([
            "success" => false,
            "error" => "Erro ao salvar: " . $nome
        ]);
        exit;
    }

    
    $postFields["mediaFile[$i]"] = curl_file_create($destino);
}

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL => "http://127.0.0.1:5000/analisar",
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_POSTFIELDS => $postFields

]);

$resposta = curl_exec($curl);

if ($resposta === false) {

    echo json_encode([
        "success" => false,
        "error" => curl_error($curl)
    ]);


    foreach ($postFields as $arquivo) {
        @unlink($arquivo->getFilename());
    }

    exit;
}

curl_close($curl);

foreach ($postFields as $arquivo) {
    @unlink($arquivo->getFilename());
}

$json = json_decode($resposta, true);

if ($json === null) {

    echo json_encode([
        "success" => false,
        "error" => "Resposta inválida do Flask.",
        "resposta" => $resposta
    ]);

    exit;
}

echo json_encode($json);

?>