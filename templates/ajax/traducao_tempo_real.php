<?php

header("Content-Type: application/json");

$arquivo = $_FILES["frame"];

$caminho = __DIR__ . "/temp/frame.jpg";

if (!move_uploaded_file($arquivo["tmp_name"], $caminho)) {
    echo json_encode([
        "erro" => "Não conseguiu mover."
    ]);
    exit;
}

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "http://127.0.0.1:5000/traducao_tempo_real",
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => [
        "frame" => new CURLFile($caminho)
    ]
]);

$resposta = curl_exec($curl);

if ($resposta === false) {
    echo json_encode([
        "erro" => curl_error($curl)
    ]);
    exit;
}

echo $resposta;

?>