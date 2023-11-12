<?php
$this->functionHandlers['linux_commands'] = function ($args) {
    if ($this->ash->debug) echo ("debug: linux_command(" . print_r($args, true) . ")\n");
    $procExec = function (array $input): array {
        if ($this->ash->debug) echo ("debug: proc_exec(" . print_r($input, true) . ")\n");
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];
        $pipes = [];
        try {
            echo ("$ " . $input['command'] . "\n");
            $this->runningProcess = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'] ?? $this->ash->sysInfo->sysInfo['workingDir'], $input['env'] ?? []);
        } catch (\Exception $e) {
            return [
                "stdout" => "",
                "stderr" => "Error (ash): proc_open() failed: " . $e->getMessage(),
                "exit_code" => -1,
            ];
        }
        if (is_resource($this->runningProcess)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($this->runningProcess);
            $this->runningProcess = null;
            $result = [
                "stdout" => $stdout,
                "stderr" => $stderr,
                "exitCode" => $exitCode,
            ];
            if ($this->ash->debug) echo ("debug: proc_exec() result: " . print_r($result, true) . "\n");
            return $result;
        }
        return [
            "stdout" => "",
            "stderr" => "Error (ash): proc_open() failed.",
            "exitCode" => -1,
        ];
    };
    $result = $procExec($args);
    if ($this->ash->debug) echo ("debug: linux_command() result: " . print_r($result, true) . "\n");
    return $result;
};
