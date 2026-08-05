<?php

header("Content-Type: application/json");

$pasta = __DIR__ . "/temp/";

if (is_dir($pasta)) {

    foreach (glob($pasta . "*") as $arquivo) {

        if (is_file($arquivo)) {
            unlink($arquivo);
        }

    }

}

echo json_encode([
    "success" => true
]);

?>