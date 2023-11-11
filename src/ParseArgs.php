<?php

namespace Rpurinton\Ash;

class ParseArgs
{
    public function parseArgs($ash)
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
                    $ash->config->initialConfig();
                    break;
                case "/d":
                    $ash->debug = true;
                    echo "(ash) Debug mode enabled.\n";
                    break;
                case "/r":
                    (new Composer())->install_dependencies($ash->debug);
                    break;
                case "/x":
                    shell_exec("rm -rfv " . __DIR__ . "/conf.d");
                    shell_exec("rm -rfv " . __DIR__ . "/vendor");
                    shell_exec("rm -rfv " . __DIR__ . "/composer.lock");
                    die("(ash) Uninstalled.\n");
            }
        }
    }
}
