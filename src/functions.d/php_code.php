<?php
$this->functionHandlers['php_code'] = function ($args) {
    if ($this->ash->debug) echo ("debug: php_code(" . print_r($args, true) . ")\n");
    $php_code = $args['php_code'] ?? "";
    $stdOut = "";
    $sdtErr = "";
    $exitCode = 0;
    if (empty($php_code)) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: php_code() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }
    $random_file = "/tmp/ash_php_code_" . uniqid() . ".php";
    file_put_contents($random_file, $php_code);
    $command = "php $random_file";
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
        $sdtErr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
    }
    $result = ["stdout" => $stdOut, "stderr" => $sdtErr, "exit_code" => $exitCode];
    if ($this->ash->debug) echo ("debug: php_code() result: " . print_r($result, true) . "\n");
    return $result;
};
