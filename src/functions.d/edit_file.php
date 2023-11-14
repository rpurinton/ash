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
    exec($command, $output, $exitCode);

    if ($dry_run) {
        $stdOut = implode("\n", $output);
    } else {
        $stdOut = $exitCode === 0 ? "File edited successfully." : "No changes made.";
    }

    $stdErr = $exitCode === 0 ? "No errors." : "Error occurred with exit code: $exitCode";

    return ["stdout" => $stdOut, "stderr" => $stdErr, "exit_code" => $exitCode];
};
