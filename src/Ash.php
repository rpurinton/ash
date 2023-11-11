<?php

namespace Rpurinton\Ash;

class Ash
{
    private string $prompt = "";
    public bool $debug = false;
    private $running_process = null;
    private $openai = null;
    public $config = null;
    public $commands = null;
    public $sysInfo = null;

    public function __construct()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo "(ash) Error: This program is for Linux only.\n";
            exit(1);
        }
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once(__DIR__ . "/Composer.php");
            (new Composer())->install_dependencies($this->debug);
        }
        require_once(__DIR__ . "/Configuration.php");
        $this->config = new Configuration();
        (new ParseArgs)->parseArgs($this);
        $this->debug = $this->debug || $this->config->config['debug'];
        $this->sysInfo = new SysInfo($this);
        $this->openai = new OpenAI($this);
        $this->commands = new Commands($this);
        $this->run();
    }

    private function ctrl_c($signo)
    {
        if ($this->running_process) proc_terminate($this->running_process);
        echo "\n";
    }

    private function run()
    {
        passthru("clear");
        pcntl_signal(SIGINT, [$this, "ctrl_c"]);
        while (true) {
            $this->sysInfo->refresh();
            if ($this->config->config['colorSupport']) $this->prompt = "(ash) [{$this->sysInfo->sysInfo['userId']}@{$this->sysInfo->sysInfo['hostName']} \e[35m{$this->sysInfo->sysInfo['workingFolder']}\e[0m]# ";
            else $this->prompt = "(ash) [{$this->sysInfo->sysInfo['userId']}@{$this->sysInfo->sysInfo['hostName']} {$this->sysInfo->sysInfo['workingFolder']}]# ";
            $input = readline($this->prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($this->debug) echo ("(ash) input: $input\n");
            $internal_command_result = $this->commands->internalCommands($input);
            if ($internal_command_result !== false) {
                echo $internal_command_result;
                continue;
            }
            $this->openai->userMessage($input);
        }
    }
}
