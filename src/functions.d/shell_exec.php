<?php
$this->functionHandlers['shell_exec'] = function ($args) {
    if ($this->ash->debug) echo ("debug: shell_exec(" . print_r($args, true) . ")\n");
    $command = $args['command'];
    echo ("$ " . $command . "\n"); // display just the main argument
    // redirect stderr to stdout
    $command .= " 2>&1";
    exec($command, $output, $exitCode);
    $output = implode("\n", $output);
    $result = [
        "stdout+stderr" => $output,
        "exitCode" => $exitCode,
    ];
    if ($this->ash->debug) echo ("debug: shell_exec() result: " . print_r($result, true) . "\n");
    return $result;
};
