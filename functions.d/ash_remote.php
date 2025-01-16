<?php
$this->functionHandlers['ash_remote'] = function ($args) {
    $host = $args['host'] ?? "";
    $message = $args['message'] ?? "";
    if ($this->ash->debug) echo ("debug: ash_remote() host: $host, message: $message\n");

    echo ("$ " . $host . ": " . $message . "\n"); // display just the main argument

    $host = escapeshellarg($host);
    $message = escapeshellarg($message);
    // Use SSH to execute the command on the remote host
    $sshCommand = "ssh $host \"ash /m $message\"";
    exec($sshCommand, $output, $return_var);

    $result = [
        "stdout" => implode("\n", $output),
        "stderr" => $return_var === 0 ? "" : "Error (ash): SSH command failed",
        "exit_code" => $return_var
    ];

    if ($this->ash->debug) echo ("debug: ash_remote() result: " . print_r($result, true) . "\n");
    return $result;
};
