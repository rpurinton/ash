<?php

namespace Rpurinton\Ash;

class Ash
{
    private string $prompt = "";
    public array $sys_info = [];
    public bool $debug = false;
    private $running_process = null;
    private $openai = null;
    public $config = [];

    public function __construct()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo "(ash) Error: This program is for Linux only.\n";
            exit(1);
        }
        $this->parse_args();
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) $this->install_dependencies();
        if (!file_exists(__DIR__ . '/conf.d/config.json')) $this->initial_config();
        $this->config = json_decode(file_get_contents(__DIR__ . '/conf.d/config.json'), true);
        $this->debug = $this->debug || $this->config['debug'];
        if ($this->debug) echo "(ash) config: " . print_r($this->config, true) . "\n";
        $this->set_system_info();
        if ($this->debug) echo "(ash) sys_info: " . print_r($this->sys_info, true) . "\n";
        require_once(__DIR__ . "/OpenAI.php");
        $this->openai = new OpenAI($this);
        $this->run();
    }

    private function save_config()
    {
        file_put_contents(__DIR__ . '/conf.d/config.json', json_encode($this->config, JSON_PRETTY_PRINT));
    }

    private function install_dependencies()
    {
        echo "(ash) Installing dependencies...";
        $cmd = "cd " . __DIR__ . " && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && composer install 2>&1";
        exec($cmd, $output, $exit_code);
        if ($this->debug) echo "(ash) install_dependencies() result: " . print_r($output, true) . "\n";
        if ($exit_code != 0) {
            echo "failed.\n";
            echo "(ash) Error: composer install failed.\n";
            exit(1);
        }
        echo "done.\n";
    }

    public function initial_config()
    {
        echo ("(ash) Initial configuration wizard...\n");
        $openai_api_key = "";
        while (true) {
            $openai_api_key = readline("(ash) Enter your OpenAI API key: ");
            if (strlen($openai_api_key) == 51 && substr($openai_api_key, 0, 3) == "sk-") break;
            echo "(ash) Error: Invalid API key.\n";
        }
        $color_support = readline("(ash) Enable \e[31mcolor \e[32msupport?\e[0m [Y/n]: ");
        $color_support = strtolower(substr($color_support, 0, 1));
        if ($color_support == "y" || $color_support == "") $color_support = true;
        else $color_support = false;
        $emoji_support = readline("(ash) Enable emoji support? ✅ [Y/n]: ");
        $emoji_support = strtolower(substr($emoji_support, 0, 1));
        if ($emoji_support == "y" || $emoji_support == "") $emoji_support = true;
        else $emoji_support = false;

        $debug = readline("(ash) Enable debug mode? [y/N]: ");
        $debug = strtolower(substr($debug, 0, 1));
        if ($debug == "y") $debug = true;
        else $debug = false;
        $this->config = [
            "openai_api_key" => $openai_api_key,
            "color_support" => $color_support,
            "emoji_support" => $emoji_support,
            "debug" => $debug,
        ];
        if (!is_dir(__DIR__ . '/conf.d')) mkdir(__DIR__ . '/conf.d', 0755, true);
        $this->save_config();
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
                    $this->initial_config();
                    break;
                case "/d":
                    $this->debug = true;
                    echo "(ash) Debug mode enabled.\n";
                    break;
                case "/r":
                    $this->install_dependencies();
                    break;
                case "/x":
                    shell_exec("rm -rfv " . __DIR__ . "/conf.d");
                    shell_exec("rm -rfv " . __DIR__ . "/vendor");
                    shell_exec("rm -rfv " . __DIR__ . "/composer.lock");
                    die("(ash) Uninstalled.\n");
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
        // if this is the home_dir change it to just ~
        if ($this->sys_info['working_dir'] == $this->sys_info['home_dir']) $this->sys_info['working_folder'] = "~";
    }

    private function run()
    {
        pcntl_signal(SIGINT, [$this, "ctrl_c"]);
        while (true) {
            $this->set_system_info();
            if ($this->config['color_support']) $this->prompt = "(ash) [{$this->sys_info['user_id']}@{$this->sys_info['host_name']} \e[35m{$this->sys_info['working_folder']}\e[0m]# ";
            else $this->prompt = "(ash) [{$this->sys_info['user_id']}@{$this->sys_info['host_name']} {$this->sys_info['working_folder']}]# ";
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
            case "bash":
                passthru(trim(shell_exec("which bash")));
                return "";
            case "help":
                return file_get_contents(__DIR__ . "/../README.md") . "\n";
            case "sys_info":
                return print_r($this->sys_info, true) . "\n";
            case "debug":
                $this->debug = !$this->debug;
                $this->config['debug'] = $this->debug;
                $this->save_config();
                if ($this->debug) return "(ash) Debug mode enabled.\n";
                else return "(ash) Debug mode disabled.\n";
            case "config":
                $this->initial_config();
                return "";
            case "color":
                $this->config["color_support"] = !$this->config["color_support"];
                $this->save_config();
                if ($this->config["color_support"]) return "(ash) Color support enabled.\n";
                else return "(ash) Color support disabled.\n";
            case "emoji":
                $this->config["emoji_support"] = !$this->config["emoji_support"];
                $this->save_config();
                if ($this->config["emoji_support"]) return "(ash) Emoji support enabled 🙂\n";
                else return "(ash) Emoji support disabled.\n";
            case "openai-key":
                $openai_api_key = "";
                while (true) {
                    $openai_api_key = readline("(ash) Enter your OpenAI API key: ");
                    if (strlen($openai_api_key) == 51 && substr($openai_api_key, 0, 3) == "sk-") break;
                    echo "(ash) Error: Invalid API key.\n";
                }
                $this->config["openai_api_key"] = $openai_api_key;
                $this->save_config();
                return "(ash) OpenAI API key updated.\n";
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
        try {
            $this->running_process = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'], $input['env_vars'], $input['options']);
        } catch (\Exception $e) {
            return [
                "stdout" => "",
                "stderr" => "Error (ash): proc_open() failed: " . $e->getMessage(),
                "exit_code" => -1,
            ];
        }
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
