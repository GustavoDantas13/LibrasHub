<?php

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
ini_set("log_errors", "1");

error_reporting(E_ALL);

set_time_limit(3000);


function responder(
    array $dados,
    int $codigo = 200
): void {

    http_response_code($codigo);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function converterBytes(string $valor): int {

    $valor = trim($valor);

    if ($valor === "") {
        return 0;
    }

    $unidade = strtolower(
        substr($valor, -1)
    );

    $numero = (float) $valor;

    switch ($unidade) {

        case "g":

            return (int) (
                $numero *
                1024 *
                1024 *
                1024
            );

        case "m":

            return (int) (
                $numero *
                1024 *
                1024
            );

        case "k":

            return (int) (
                $numero *
                1024
            );

        default:

            return (int) $numero;
    }
}


function apagarTemporario(
    ?string $caminho
): void {

    if (
        $caminho !== null &&
        is_file($caminho)
    ) {
        @unlink($caminho);
    }
}


/* Limites do PHP */

$contentLength = isset(
    $_SERVER["CONTENT_LENGTH"]
)
    ? (int) $_SERVER["CONTENT_LENGTH"]
    : 0;

$postMaxSize = ini_get(
    "post_max_size"
);

$postMaxBytes = converterBytes(
    $postMaxSize
);

$uploadMaxSize = ini_get(
    "upload_max_filesize"
);


if (
    $contentLength > 0 &&
    $postMaxBytes > 0 &&
    $contentLength > $postMaxBytes
) {

    responder([
        "success" => false,
        "error" => (
            "O arquivo ultrapassa o limite "
            . "post_max_size do PHP."
        ),
        "tamanho_recebido_bytes" =>
            $contentLength,
        "post_max_size" =>
            $postMaxSize,
        "upload_max_filesize" =>
            $uploadMaxSize
    ], 413);
}


/* Validação do upload */

if (!isset($_FILES["mediaFile"])) {

    responder([
        "success" => false,
        "error" => (
            "Nenhum arquivo foi recebido pelo PHP. "
            . "Verifique upload_max_filesize "
            . "e post_max_size."
        ),
        "content_length" =>
            $contentLength,
        "post_max_size" =>
            $postMaxSize,
        "upload_max_filesize" =>
            $uploadMaxSize
    ], 400);
}


if (
    !isset($_POST["dataset"]) ||
    trim($_POST["dataset"]) === ""
) {

    responder([
        "success" => false,
        "error" =>
            "Nome do dataset não informado."
    ], 400);
}


$erroUpload =
    $_FILES["mediaFile"]["error"];


if ($erroUpload !== UPLOAD_ERR_OK) {

    $mensagens = [

        UPLOAD_ERR_INI_SIZE =>
            "O arquivo ultrapassa upload_max_filesize.",

        UPLOAD_ERR_FORM_SIZE =>
            "O arquivo ultrapassa o limite do formulário.",

        UPLOAD_ERR_PARTIAL =>
            "O arquivo foi enviado parcialmente.",

        UPLOAD_ERR_NO_FILE =>
            "Nenhum arquivo foi enviado.",

        UPLOAD_ERR_NO_TMP_DIR =>
            "A pasta temporária do PHP não existe.",

        UPLOAD_ERR_CANT_WRITE =>
            "O PHP não conseguiu gravar o arquivo temporário.",

        UPLOAD_ERR_EXTENSION =>
            "Uma extensão do PHP interrompeu o upload."
    ];

    responder([
        "success" => false,
        "error" =>
            $mensagens[$erroUpload]
            ?? "Erro desconhecido no upload.",
        "codigo_upload" =>
            $erroUpload
    ], 400);
}


if (!function_exists("curl_init")) {

    responder([
        "success" => false,
        "error" =>
            "A extensão cURL não está habilitada."
    ], 500);
}


/* Arquivo temporário */

$dataset = trim(
    $_POST["dataset"]
);

$tmp = $_FILES["mediaFile"][
    "tmp_name"
];

$nomeOriginal = basename(
    $_FILES["mediaFile"]["name"]
);


if (
    $nomeOriginal === "" ||
    !is_uploaded_file($tmp)
) {

    responder([
        "success" => false,
        "error" =>
            "O arquivo recebido é inválido."
    ], 400);
}


$tempDir =
    __DIR__
    . DIRECTORY_SEPARATOR
    . "temp";


if (
    !is_dir($tempDir) &&
    !mkdir($tempDir, 0777, true)
) {

    responder([
        "success" => false,
        "error" =>
            "Não foi possível criar a pasta temporária."
    ], 500);
}


$destino =
    $tempDir
    . DIRECTORY_SEPARATOR
    . uniqid("dataset_", true)
    . "_"
    . $nomeOriginal;


if (!move_uploaded_file($tmp, $destino)) {

    responder([
        "success" => false,
        "error" =>
            "Não foi possível salvar o arquivo temporário."
    ], 500);
}


if (!is_file($destino)) {

    responder([
        "success" => false,
        "error" =>
            "O arquivo temporário não foi encontrado."
    ], 500);
}


$mime = mime_content_type(
    $destino
);

if (!$mime) {
    $mime = "application/octet-stream";
}


/* Envio ao Flask */

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
        "http://127.0.0.1:5000/criar_dataset",

    CURLOPT_POST => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POSTFIELDS => [

        "dataset" => $dataset,

        "mediaFile" => new CURLFile(
            $destino,
            $mime,
            $nomeOriginal
        )
    ],

    CURLOPT_CONNECTTIMEOUT => 15,

    CURLOPT_TIMEOUT => 300,

    CURLOPT_HTTPHEADER => [
        "Accept: application/json"
    ]
]);


$resposta = curl_exec(
    $curl
);

$erroCurl = curl_error(
    $curl
);

$codigoHttp = curl_getinfo(
    $curl,
    CURLINFO_HTTP_CODE
);


/* O Flask já recebeu o arquivo */

apagarTemporario(
    $destino
);


/* Erro de comunicação */

if ($resposta === false) {

    responder([
        "success" => false,
        "error" =>
            "Falha ao comunicar com o Flask.",
        "detalhes" =>
            $erroCurl
    ], 502);
}


/* Validação da resposta */

$json = json_decode(
    $resposta,
    true
);


if (!is_array($json)) {

    responder([
        "success" => false,
        "error" =>
            "O Flask retornou uma resposta inválida.",
        "http_code_flask" =>
            $codigoHttp,
        "resposta_flask" =>
            $resposta,
        "erro_json" =>
            json_last_error_msg()
    ], 502);
}


if (
    $codigoHttp < 200 ||
    $codigoHttp >= 300
) {

    $json["success"] = false;

    $json["http_code_flask"] =
        $codigoHttp;

    responder(
        $json,
        $codigoHttp
    );
}


/*
 * Esta validação depende da rota Flask retornar:
 *
 * "rota": "criar_dataset"
 * "existe": true
 */

if (
    ($json["rota"] ?? "") !==
        "criar_dataset" ||
    ($json["existe"] ?? false) !== true
) {

    responder([
        "success" => false,
        "error" => (
            "O Flask respondeu, mas não confirmou "
            . "o salvamento físico do arquivo."
        ),
        "resposta_flask" =>
            $json
    ], 502);
}


responder(
    $json,
    200
);