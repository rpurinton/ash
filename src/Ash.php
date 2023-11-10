<?php

namespace Rpurinton\Ash;

class Ash
{
    private $uptime;
    private $host_fqdn;
    private $host_name;
    private $user_id;
    private $working_dir;
    private $working_folder;
    private $debug;

    public function __construct()
    {
        global $argv;
        foreach ($argv as $arg) switch (substr($arg, 0, 2)) {
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
        $this->uptime = trim(shell_exec("uptime"));
        $this->host_fqdn = trim(shell_exec("hostname"));
        $this->host_name = trim(shell_exec("hostname -s"));
        $this->user_id = trim(shell_exec("whoami"));
        $this->working_dir = trim(shell_exec("pwd"));
        $this->working_folder = basename($this->working_dir);
        $this->working_folder = $this->working_folder == "" ? "/" : $this->working_folder;
        if ($this->debug) echo "uptime: $this->uptime\nhost_fqdn: $this->host_fqdn\nhost_name: $this->host_name\nuser_id: $this->user_id\nworking_dir: $this->working_dir\nworking_folder: $this->working_folder\n";
        $this->run();
    }

    public function run()
    {
        $prompt = "ash# ";

        while (true) {
            $input = readline($prompt);
            readline_add_history($input);
            $input = trim($input);
            if ($input == "exit") {
                break;
            }
            $output = shell_exec($input);
            echo $output;
        }
    }
}
