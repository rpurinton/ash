<?php

namespace Rpurinton\Ash;

class Ash
{
    private string $prompt = "";
    private array $sys_info = [];
    private bool $debug = false;
    private $running_process = null;

    public function __construct()
    {
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) $this->install_dependencies();
        require_once(__DIR__ . "/OpenAI.php");
        pcntl_signal(SIGINT, [$this, "ctrl_c"]);
        $this->parse_args();
        $this->run();
    }

    private function install_dependencies()
    {
        echo "(ash) Installing dependencies...";
        $composer = trim(shell_exec("which composer"));
        print_r($this->proc_exec([
            "command" => $composer . " install",
            "cwd" => __DIR__,
            "env_vars" => [
                "COMPOSER_ALLOW_SUPERUSER" => "1",
                "COMPOSER_NO_INTERACTION" => "1",
            ],
            "options" => [],
        ]));
        echo "done.\n";
    }

    private function ctrl_c($signo)
    {
        if ($this->running_process) proc_terminate($this->running_process);
        echo "\n";
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
            'release' => trim(shell_exec("cat /etc/*release*")),
            'uname_a' => trim(shell_exec("uname -a")),
            'host_fqdn' => trim(shell_exec("hostname")),
            'host_name' => trim(shell_exec("hostname -s")),
            'ip_addr' => trim(shell_exec("ip addr | grep inet")),
            'etc_hosts' => trim(shell_exec("cat /etc/hosts")),
            'uptime' => trim(shell_exec("uptime")),
            'free_mh' => trim(shell_exec("free -mh")),
            'df_h' => trim(shell_exec("df -h")),
            'user_id' => trim(shell_exec("whoami")),
            'home_dir' => trim(shell_exec("echo ~")),
            'last_dir' => isset($this->sys_info['last_dir']) ? $this->sys_info['last_dir'] : trim(shell_exec("pwd")),
            'working_dir' => trim(shell_exec("pwd")),
        ];
        $this->sys_info['working_folder'] = basename($this->sys_info['working_dir'] == "" ? "/" : basename($this->sys_info['working_dir']));
        if ($this->debug) echo ("(ash) set_system_info() result: " . print_r($this->sys_info, true) . "\n");
    }

    private function run()
    {
        while (true) {
            $this->set_system_info();
            $this->prompt = "[{$this->sys_info['user_id']}@{$this->sys_info['host_name']} {$this->sys_info['working_folder']}] (ash)# ";
            $input = readline($this->prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($this->debug) echo ("(ash) input: $input\n");
            $internal_command_result = $this->internal_commands($input);
            if ($internal_command_result !== false) {
                echo $internal_command_result;
                continue;
            }
            echo $this->execute_command($input);
        }
    }

    private function internal_commands($input)
    {
        switch ($input) {
            case "":
                return "";
            case "exit":
            case "quit":
                exit(0);
            case "help":
                return file_get_contents(__DIR__ . "/../README.md") . "\n";
            case "sys_info":
                return print_r($this->sys_info, true) . "\n";
        }
        if (substr($input, 0, 3) == "cd ") {
            $this->change_directory(substr($input, 3));
            return "";
        }
        return false;
    }

    private function change_directory($target_dir)
    {
        if ($target_dir == "~") $target_dir = $this->sys_info['home_dir'];
        if ($target_dir == "-") $target_dir = $this->sys_info['last_dir'];
        if (is_dir($target_dir)) {
            $this->sys_info['last_dir'] = $this->sys_info['working_dir'];
            chdir($target_dir);
        } else echo "(ash) Error: Directory not found: $target_dir\n";
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
