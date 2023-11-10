#!/usr/local/bin/php -f
<?php

namespace Rpurinton\ash;

foreach ($argv as $arg) switch (substr($arg, 0, 2)) {
    case "/v":
        die("ash version 0.0.1 rpurinton 2023\n");
    case "/h":
        die(shell_exec("cat " . __DIR__ . "/README.md") . "\n");
    case "/l":
        die(shell_exec("cat " . __DIR__ . "/LICENSE") . "\n");
    case "/c":
        die(shell_exec("cat " . __DIR__ . "/CREDITS") . "\n");
    case "/d":
        $debug = true;
        break;
}

$uptime = trim(shell_exec("uptime"));
$host_fqdn = trim(shell_exec("hostname"));
$host_name = trim(shell_exec("hostname -s"));
$user_id = trim(shell_exec("whoami"));
$working_dir = trim(shell_exec("pwd"));
$working_folder = basename($working_dir);
$working_folder = $working_folder == "" ? "/" : $working_folder;

if ($debug) {
    echo "uptime: $uptime\nhost_fqdn: $host_fqdn\nhost_name: $host_name\nuser_id: $user_id\nworking_dir: $working_dir\nworking_folder: $working_folder\n";
}

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
