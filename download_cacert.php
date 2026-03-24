<?php
$context = stream_context_create([
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ],
]);
$data = file_get_contents("https://curl.se/ca/cacert.pem", false, $context);
if ($data) {
    file_put_contents(__DIR__ . "/cacert.pem", $data);
    echo "SUCCESS";
} else {
    echo "FAILED";
}
