<?php
$this->functionHandlers['bash_script'] = function ($args) {
    if ($this->ash->debug) echo ("debug: bash_script(" . print_r($args, true) . ")\n");
    $bash_script = $args['script'] ?? "";
    $stdOut = "";
    $sdtErr = "";
    $exitCode = 0;
    if (empty($bash_script)) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: bash_script() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    $random_file = "/tmp/ash_bash_script_" . uniqid() . ".php";
    file_put_contents($random_file, $bash_script);
    $command = "bash $random_file";
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
    unlink($random_file);
    echo ("done!\n"); // display just the main argument
    if ($stdOut == "" && $stdErr == "" && $exitCode == 0) {
        $stdOut = "Clean exit, no output.";
    }
    if ($stdErr == "") $stdErr = $exitCode == 0 ? "No errors." : "Error (ash): Process exited with non-zero exit code.";
    $result = ["stdout" => $stdOut, "stderr" => $sdtErr, "exit_code" => $exitCode];
    if ($this->ash->debug) echo ("debug: bash_script() result: " . print_r($result, true) . "\n");
    return $result;
};
