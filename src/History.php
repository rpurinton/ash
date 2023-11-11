<?php

namespace Rpurinton\Ash;

class History
{
    private $historyFile = shell_exec("echo ~") . "/.ash_history.jsonl";
    private $history = [];

    public function __construct(private $util)
    {
        $this->loadHistory();
    }

    public function __toArray()
    {
        return $this->history;
    }

    public function loadHistory()
    {
        if (file_exists($this->historyFile)) {
            $history_jsonl = file_get_contents($this->historyFile);
            $history_jsonl = explode("\n", $history_jsonl);
            foreach ($history_jsonl as $history_json) {
                if ($history_json == "") continue;
                $history = json_decode($history_json, true);
                if (!is_null($history)) $this->history[] = $history;
            }
        } else {
            $this->history = [];
        }
    }

    public function saveMessage($message)
    {
        $message["tokens"] = $this->util->tokenCount($message["content"]);
        $this->history[] = $message;
        file_put_contents($this->historyFile, json_encode($message) . "\n", FILE_APPEND);
    }

    public function getHistory($num_tokens)
    {
        $rev_history = array_reverse($this->history);
        $token_count = 0;
        $result = [];
        foreach ($rev_history as $message) {
            $token_count += $message["tokens"];
            if ($token_count <= $num_tokens) {
                unset($message["tokens"]);
                $result[] = $message;
            } else {
                $token_count -= $message["tokens"];
                break;
            }
        }
        return array_reverse($result);
    }

    public function clearHistory()
    {
        $this->history = [];
        file_put_contents($this->historyFile, "");
    }
}
