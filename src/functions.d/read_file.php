<?php
$this->functionHandlers['read_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: read_file(" . print_r($args, true) . ")\n");
    $path = $args['path'] ?? "";
    echo ("$path\n"); // display just the main argument
    if (!file_exists($path)) {
        if ($this->ash->debug) echo ("debug: read_file() error: file not found: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file not found: $path", "exit_code" => -1];
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        if ($this->ash->debug) echo ("debug: read_file() error: file could not be read: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file could not be read: $path", "exit_code" => -1];
    }
    if ($contents = "") {
        if ($this->ash->debug) echo ("debug: read_file() error: file is empty: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file is empty: $path", "exit_code" => -1];
    }
    $result = ["stdout" => $contents, "stderr" => "", "exit_code" => 0];
    if ($this->ash->debug) echo ("debug: read_file() result: " . print_r($result, true) . "\n");
    return $result;
};
