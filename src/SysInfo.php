<?php

namespace Rpurinton\Ash;

class SysInfo
{
    public $sysInfo = [];

    public function __construct(private $ash)
    {
        $this->refresh();
        if ($ash->debug) echo "sysInfo: " . print_r($this->sysInfo, true) . "\n";
    }

    public function refresh()
    {
        $this->sysInfo = [
            'release' => trim(shell_exec("cat /etc/*release*") ?? ''),
            'uname-a' => trim(shell_exec("uname -a") ?? ''),
            'hostFQDN' => trim(shell_exec("hostname") ?? ''),
            'hostName' => trim(shell_exec("hostname -s") ?? ''),
            'ipAddr' => trim(shell_exec("ip addr | grep inet") ?? ''),
            'etcHosts' => trim(shell_exec("cat /etc/hosts") ?? ''),
            'uptime' => trim(shell_exec("uptime") ?? ''),
            'free-mh' => trim(shell_exec("free -mh") ?? ''),
            'df-h' => trim(shell_exec("df -h") ?? ''),
            'failedServices' => trim(shell_exec("systemctl --failed") ?? ''),
            'listeningPorts' => trim(shell_exec("ss -tunalp | grep -v chromium-browse") ?? ''),
            'emergencyContact' => 'not set',
            'ashEmailAddress' => 'not set',
            'who-u' => trim(shell_exec("who -u") ?? ''),
            'termColorSupport' => $this->ash->config->config['colorSupport'] ? "\e[32myes\e[0m" : "no",
            'termEmojiSupport' => $this->ash->config->config['emojiSupport'] ? "✅" : "no",
            'terminalLines' => trim(shell_exec("tput lines 2> /dev/null") ?? ''),
            'terminalColumns' => trim(shell_exec("tput cols 2> /dev/null") ?? ''),
            'currentDate' => trim(shell_exec("date") ?? ''),
            'userId' => trim(shell_exec("whoami") ?? ''),
            'homeDir' => trim(shell_exec("echo ~") ?? ''),
            'lastDir' => isset($this->sysInfo['lastDir']) ? $this->sysInfo['lastDir'] : trim(shell_exec("pwd") ?? ''),
            'workingDir' => trim(shell_exec("pwd") ?? ''),
        ];
        $this->sysInfo['workingFolder'] = basename($this->sysInfo['workingDir'] == "" ? "/" : basename($this->sysInfo['workingDir']));
        if ($this->sysInfo['workingDir'] == $this->sysInfo['homeDir']) $this->sysInfo['workingFolder'] = "~";
        if ($this->ash->config->config['emailAddress'] != "") $this->sysInfo['emergencyContact'] = $this->ash->config->config['emailAddress'];
        if ($this->ash->config->config['fromAddress'] != "") $this->sysInfo['ashEmailAddress'] = $this->ash->config->config['fromAddress'];
    }

    public function setLastDir($dir)
    {
        $this->sysInfo['lastDir'] = $dir;
    }
}
