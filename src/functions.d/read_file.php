<?php
$this->functionHandlers['read_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: read_file(" . print_r($args, true) . ")\n");
    $path = $args['path'] ?? "";
    if (!file_exists($path)) {
        if ($this->ash->debug) echo ("debug: read_file() error: file not found: $path\n");
        return ["stderr" => "Error (ash): file not found: $path", "exit_code" => -1];
    }
    $result = ["content" => file_get_contents($path)];
    if ($this->ash->debug) echo ("debug: read_file() result: " . print_r($result, true) . "\n");
    return $result;
};
