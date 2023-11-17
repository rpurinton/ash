<?php
$this->functionHandlers['edit_file'] = function ($args) {
    $sed_command = $args['sed_command'] ?? null;
    $stdOut = $stdErr = '';
    $exitCode = 0;

    if (!$sed_command) {
        $error = "Error: 'sed_command' is required.";
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    echo ($sed_command . "\n"); // display just the main argument
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"], // stderr
    ];
    $process = proc_open($sed_command, $descriptorspec, $pipes);
    if (!is_resource($process)) {
        $error = "Error: Unable to open process.";
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    $stdOut = stream_get_contents($pipes[1]);
    $stdErr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($stdOut == "" && $stdErr == "" && $exitCode == 0) $stdOut = "Exited cleanly with no output.";
    return ["stdout" => $stdOut, "stderr" => $stdErr, "exit_code" => $exitCode];
};
