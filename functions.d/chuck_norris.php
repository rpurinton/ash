<?php
$this->functionHandlers['chuck_norris'] = function ($args) {
    $url = 'https://api.chucknorris.io/jokes/random';
    if ($this->ash->debug) echo ("debug: chuck_norris() url: $url\n");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $joke = json_decode($output, true);
    $result = [
        "stdout" => $joke['value'],
        "stderr" => json_last_error() === JSON_ERROR_NONE ? "" : "Error (ash): Failed to decode JSON",
        "exit_code" => json_last_error() === JSON_ERROR_NONE ? 0 : 1
    ];

    echo ("done.\n");

    if ($this->ash->debug) echo ("debug: chuck_norris_joke() result: " . print_r($result, true) . "\n");
    return $result;
};
