<?php
$this->functionHandlers['edit_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: edit_file(" . print_r($args, true) . ")\n");
    $sed_command = $args['sed_command'] ?? "";
    echo ("$ " . $sed_command . "\n"); // display just the main argument
    $stdOut = "";
    $sdtErr = "";
    $exitCode = 0;
    if (empty($sed_command)) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: edit_file() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    $command = $sed_command;
    if ($this->ash->debug) echo ("debug: edit_file() command: $command\n");
    $env = trim(shell_exec("env | grep -v '^BASH_FUNC_'"));
    // parse it into key-value pairs
    $env = explode("\n", $env);
    $env = array_map(function ($item) {
        $item = explode("=", $item);
        if (isset($item[1])) return [$item[0] => $item[1]];
        else return [];
    }, $env);
    $env = array_reduce($env, function ($carry, $item) {
        return array_merge($carry, $item);
    }, []);
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    $process = proc_open($command, $descriptorspec, $pipes, null, $env);
    if (is_resource($process)) {
        $stdOut = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stdErr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
    }
    if ($stdOut == "" && $stdErr == "" && $exitCode == 0) {
        $stdOut = "Clean exit, no output.";
    }
    if ($stdErr == "") $stdErr = $exitCode == 0 ? "No errors." : "Error (ash): Process exited with non-zero exit code.";
    $result = ["stdout" => $stdOut, "stderr" => $sdtErr, "exit_code" => $exitCode];
    if ($this->ash->debug) echo ("debug: read_file() result: " . print_r($result, true) . "\n");
    return $result;
};
