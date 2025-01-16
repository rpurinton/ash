<?php
$this->functionHandlers['web_browser'] = function ($args) {
    if ($this->ash->debug) echo ("debug: web_browser(" . print_r($args, true) . ")\n");
    if (!isset($args['url']) || is_null($args['url']) || $args['url'] == "") return [
        "stdErr" => "Error (ash): Missing required field 'url'.",
        "exitCode" => -1
    ];
    $url = $args['url'];
    echo ("$url\n"); // display just the main argument
    $method = $args['method'] ?? "GET";
    $headers = $args['headers'] ?? [];
    $headers["User-Agent"] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36";
    $body = $args['body'] ?? "";
    $responseType = $args['response_type'] ?? "text";


    $valid_responseTypes = [
        "html",
        "text",
        "links",
        "images",
        "text+links",
        "text+images",
        "links+images",
        "text+links+images",
        "text+links-inline",
        "text+images-inline",
        "text+links+images-inline",
    ];
    if (!in_array($responseType, $valid_responseTypes)) return [
        "stderr" => "Error (ash): Invalid response_type '$responseType'.",
        "exit_code" => -1
    ];

    $request = [
        "url" => $url,
        "method" => $method,
        "headers" => $headers,
        "body" => $body,
        "response_type" => "text"
    ];

    $response = file_get_contents("https://puppeteer2.discommand.com", false, stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n",
            "content" => json_encode($request)
        ]
    ]));

    if (!isset($response) || is_null($response) || $response == "") return [
        "stdErr" => "Error (ash): No response from web browser.",
        "exitCode" => -1
    ];

    $response = print_r(json_decode($response, true), true);
    if ($this->ash->debug) echo ("debug: web_browser() response: $response\n");
    $result = ["stdOut" => $response, "stdErr" => "", "exitCode" => 0];
    return $result;
};
