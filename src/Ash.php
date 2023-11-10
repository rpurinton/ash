<?php

namespace Rpurinton\Ash;

class Ash
{
    private $sys_info;
    private $debug;

    public function __construct()
    {
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
            'uptime' => trim(shell_exec("uptime")),
            'host_fqdn' => trim(shell_exec("hostname")),
            'host_name' => trim(shell_exec("hostname -s")),
            'user_id' => trim(shell_exec("whoami")),
            'working_dir' => trim(shell_exec("pwd")),
        ];
        $this->sys_info['working_folder'] = basename($this->sys_info['working_dir'] == "" ? "/" : basename($this->sys_info['working_dir']));
        if ($this->debug) {
            echo "uptime: {$this->sys_info['uptime']}\nhost_fqdn: {$this->sys_info['host_fqdn']}\nhost_name: {$this->sys_info['host_name']}\nuser_id: {$this->sys_info['user_id']}\nworking_dir: {$this->sys_info['working_dir']}\nworking_folder: {$this->sys_info['working_folder']}\n";
        }
    }
    private function run()
    {
        $prompt = "[{$this->sys_info['user_id']}@{$this->sys_info['host_name']} {$this->sys_info['working_folder']}] (ash)# ";

        while (true) {
            $input = readline($prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($input == "exit") {
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
        // Use proc_open() instead of shell_exec(): proc_open() is a more secure way to execute shell commands from PHP. It allows you to specify the environment variables, working directory, and other options for the command.  
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];
        $pipes = [];
        $process = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'], $input['env_vars'], $input['options']);
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $result = [
                "stdout" => $stdout,
                "stderr" => $stderr,
            ];
            //if ($this->debug) echo ("(ash) proc_exec() result: " . print_r($result, true) . "\n");
            return $result;
        }
        return [
            "stdout" => "",
            "stderr" => "Error (ash): proc_open() failed.",
        ];
    }
}
