<?php
$this->functionHandlers['edit_file'] = function ($args) {
    $file_path = $args['file_path'] ?? null;
    $sed_command = $args['sed_command'] ?? null;
    $dry_run = $args['dry_run'] ?? false;
    $stdOut = $stdErr = '';
    $exitCode = 0;

    if (!$file_path || !$sed_command) {
        $error = "Error: 'file_path' and 'sed_command' are required.";
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }

    echo ($file_path . "\n"); // display just the main argument

    $command = "sed -i '' " . ($dry_run ? "-n 'p' " : "") . "'$sed_command' '$file_path'";
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"], // stderr
    ];
    $process = proc_open($command, $descriptorspec, $pipes);
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
