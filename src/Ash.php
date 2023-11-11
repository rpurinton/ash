<?php

namespace Rpurinton\Ash;

class Ash
{
    private $sys_info = [];
    private $debug = false;
    private $running_process = null;

    public function __construct()
    {
        pcntl_signal(SIGINT, function ($signo) {
            if ($this->running_process) proc_terminate($this->running_process);
            die("(ash) Exiting...\n");
        });
        $this->parse_args();
        $this->set_system_info();
        $this->run();
    }

    private function parse_args()
    {
        global $argv;
        foreach ($argv as $arg) {
            switch (substr($arg, 0, 2)) {
                case "/v":
                    die("ash version 0.0.1 rpurinton 2023\n");
                case "/h":
                    die(shell_exec("cat " . __DIR__ . "/../README.md") . "\n");
                case "/l":
                    die(shell_exec("cat " . __DIR__ . "/../LICENSE") . "\n");
                case "/c":
                    die(shell_exec("cat " . __DIR__ . "/../CREDITS") . "\n");
                case "/d":
                    $this->debug = true;
                    break;
            }
        }
    }

    private function set_system_info()
    {
        $this->sys_info = [
            'release' => trim(shell_exec("cat /etc/*release")),
            'uptime' => trim(shell_exec("uptime")),
            'host_fqdn' => trim(shell_exec("hostname")),
            'host_name' => trim(shell_exec("hostname -s")),
            'user_id' => trim(shell_exec("whoami")),
            'working_dir' => trim(shell_exec("pwd")),
            'home_dir' => trim(shell_exec("echo ~")),
        ];
        $this->sys_info['working_folder'] = basename($this->sys_info['working_dir'] == "" ? "/" : basename($this->sys_info['working_dir']));
        if ($this->debug) echo ("(ash) set_system_info() result: " . print_r($this->sys_info, true) . "\n");
    }

    private function run()
    {
        $prompt = "[{$this->sys_info['user_id']}@{$this->sys_info['host_name']} {$this->sys_info['working_folder']}] (ash)# ";
        while (true) {
            $input = readline($prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($input == "exit" || $input == "quit") {
                break;
            }
            if ($input == "") {
                continue;
            }
            if (substr($input, 0, 3) == "cd ") {
                $this->change_directory(substr($input, 3));
                continue;
            }
            echo $this->execute_command($input);
        }
    }

    private function change_directory($target_dir)
    {
        if ($target_dir == "~") {
            $target_dir = trim(shell_exec("echo ~"));
        }
        if ($target_dir == "-") {
            $target_dir = $this->sys_info['last_dir'];
        }
        if (is_dir($target_dir)) {
            $this->sys_info['last_dir'] = $this->sys_info['working_dir'];
            chdir($target_dir);
            $this->set_system_info();
        } else {
            echo "(ash) Error: Directory not found: $target_dir\n";
        }
    }

    private function execute_command($command)
    {
        return print_r($this->proc_exec([
            "command" => $command,
            "cwd" => $this->sys_info['working_dir'],
            "env_vars" => [],
            "options" => [],
        ]), true);
    }

    private function proc_exec(array $input): array
    {
        if ($this->debug) echo ("(ash) proc_exec(" . print_r($input, true) . ")\n");
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];
        $pipes = [];
        $this->running_process = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'], $input['env_vars'], $input['options']);
        if (is_resource($this->running_process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit_code = proc_close($this->running_process);
            $this->running_process = null;
            $result = [
                "stdout" => $stdout,
                "stderr" => $stderr,
                "exit_code" => $exit_code,
            ];
            //if ($this->debug) echo ("(ash) proc_exec() result: " . print_r($result, true) . "\n");
            return $result;
        }
        return [
            "stdout" => "",
            "stderr" => "Error (ash): proc_open() failed.",
            "exit_code" => -1,
        ];
    }
}
