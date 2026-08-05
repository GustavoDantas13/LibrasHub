<?php

header("Content-Type: application/json");

function limparPasta($pasta){

    if(!is_dir($pasta)){
        return;
    }

    foreach(glob($pasta . "/*") as $arquivo){

        if(is_file($arquivo)){
            unlink($arquivo);
        }

    }

}

limparPasta(__DIR__ . "/temp");

echo json_encode([
    "success" => true
]);

?>