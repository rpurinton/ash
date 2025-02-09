<?php
$this->toolHandlers['ssh_exec'] = function ($args) {
    $user = $args['user'] ?? "";
    $host = $args['host'] ?? "";
    $command = $args['command'] ?? "";
    if ($this->ash->debug) echo ("debug: ssh_exec() host: $host, command: $command\n");

    echo ("$ [$user@host] $command"); // display just the main argument

    // Use SSH to execute the command on the remote host
    $sshCommand = "ssh " . escapeshellarg("$user@$host") . " " . escapeshellarg($command);
    exec($sshCommand, $output, $return_var);

    $result = [
        "stdout" => implode("\n", $output),
        "stderr" => $return_var === 0 ? "" : "Error (ash): SSH command failed",
        "exit_code" => $return_var
    ];

    if ($this->ash->debug) echo ("debug: ssh_exec() result: " . print_r($result, true) . "\n");
    return $result;
};
