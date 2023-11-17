<?php
$this->functionHandlers['edit_file'] = function ($args) {
    $shell_command = $args['shell_command'] ?? null;
    $exitCode = 0;

    if (!$shell_command) {
        $error = "Error: 'shell_command' is required.";
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    echo ($shell_command . "\n"); // display just the main argument

    // if shell command doesn't already start with 'sed -i ', add it
    if (substr($shell_command, 0, 7) != "sed -i ") {
        $shell_command = "sed -i " . $shell_command;
    }

    // run the command
    exec($shell_command, $output, $exitCode);
    $output = implode("\n", $output);

    if ($output == "" && $exitCode == 0) $output = "Exited cleanly with no output.";
    return ["stdout+stderr" => $output, "exit_code" => $exitCode];
};
