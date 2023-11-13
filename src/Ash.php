<?php

namespace Rpurinton\Ash;

class Ash
{
    public string $prompt = "";
    public bool $debug = false;
    public $openai = null;
    public $config = null;
    public $commands = null;
    public $sysInfo = null;

    public function __construct()
    {

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo "Error: This program is for Linux only.\n";
            exit(1);
        }
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once(__DIR__ . "/Composer.php");
            (new Composer())->install_dependencies($this->debug);
        }
        require_once(__DIR__ . "/Configuration.php");
        $this->config = new Configuration();
        $this->debug = $this->config->config['debug'];
        $this->sysInfo = new SysInfo($this);
        $this->openai = new OpenAI($this);
        $this->commands = new Commands($this);
        (new ParseArgs)->parseArgs($this);
        $this->run();
    }

    private function ctrl_c($signo)
    {
        if ($this->openai->runningProcess) proc_terminate($this->openai->runningProcess);
        echo "\n";
    }

    private function run()
    {
        pcntl_signal(SIGINT, [$this, "ctrl_c"]);
        while (true) {
            $this->sysInfo->refresh();
            if ($this->config->config['colorSupport']) $this->prompt = "[{$this->sysInfo->sysInfo['userId']}@{$this->sysInfo->sysInfo['hostName']} \e[95m{$this->sysInfo->sysInfo['workingFolder']}\e[0m]# ";
            else $this->prompt = "[{$this->sysInfo->sysInfo['userId']}@{$this->sysInfo->sysInfo['hostName']} {$this->sysInfo->sysInfo['workingFolder']}]# ";
            $input = readline($this->prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($this->debug) echo ("debug: input: $input\n");
            $internal_command_result = $this->commands->internalCommands($input);
            if ($internal_command_result !== false) {
                echo $internal_command_result;
                continue;
            }
            $this->openai->userMessage($input);
        }
    }
}
