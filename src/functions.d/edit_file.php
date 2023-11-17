<?php
$this->functionHandlers['edit_file'] = function ($args) {
    $shell_command = $args['shell_command'] ?? null;
    $exitCode = 0;

    if (!$shell_command) {
        $error = "Error: 'shell_command' is required.";
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    echo ($shell_command . "\n"); // display just the main argument
    
    // redirect stderr to stdout
    $shell_command .= " 2>&1";

    // run the command
    exec($shell_command, $output, $exitCode);
    $output = implode("\n", $output);

    if ($output == "" && $exitCode == 0) $output = "Exited cleanly with no output.";
    return ["stdout+stderr" => $output, "exit_code" => $exitCode];
};
