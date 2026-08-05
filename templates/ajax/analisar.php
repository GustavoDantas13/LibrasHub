<?php

header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_FILES["mediaFile"])) {
    echo json_encode([
        "success" => false,
        "error" => "Nenhum arquivo recebido."
    ]);
    exit;
}


$total = count($_FILES["mediaFile"]["name"]);

for ($i = 0; $i < $total; $i++) {

    $tmp = $_FILES["mediaFile"]["tmp_name"][$i];
    $nome = basename($_FILES["mediaFile"]["name"][$i]);

    $destino = __DIR__ . "/temp/" . $nome;

    if (!move_uploaded_file($tmp, $destino)) {
        echo json_encode([
            "success" => false,
            "error" => "Erro ao salvar o arquivo: " . $nome
        ]);
        exit;
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "http://127.0.0.1:5000/analisar",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => [
            "mediaFile" => new CURLFile($destino)
        ]
    ]);

    $resposta = curl_exec($curl);


    if ($resposta === false) {

        echo json_encode([
            "success" => false,
            "error" => curl_error($curl)
        ]);

        exit;
    }

    $json = json_decode($resposta, true);

    if (file_exists($destino)) {
        unlink($destino);
    }

    if ($json === null) {

        echo json_encode([
            "success" => false,
            "error" => "Resposta inválida do Flask.",
            "resposta" => $resposta
        ]);

        exit;
    }

    if (isset($json["resultados"])) {
    foreach ($json["resultados"] as $resultado) {
        $resultados[] = $resultado;
    }
}
}




echo json_encode([
    "success" => true,
    "resultados" => $resultados
]);

?>