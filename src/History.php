<?php

namespace Rpurinton\Ash;

class History
{
    private $historyFile = "";
    private $history = [];

    public function __construct(private $util, private $ash)
    {
        $this->historyFile = __DIR__ . "/history.d/history.jsonl";
        $this->loadHistory();
    }

    public function __toArray()
    {
        return $this->history;
    }

    public function loadHistory()
    {
        if ($this->ash->debug) echo ("debug: Loading history from " . $this->historyFile . "\n");
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
        if ($this->ash->debug) echo "debug: loaded messages: " . count($this->history) . "\n";
    }

    public function saveMessage($message)
    {
        if ($this->ash->debug) echo "debug: saving message: " . print_r($message, true) . "\n";
        if ($message["role"] == "assistant" && isset($message["function_call"])) {
            $message["tokens"] = $this->util->tokenCount($message["function_call"]["arguments"]);
        } else if ($message["role"] == "function") {
            $message["tokens"] = $this->util->tokenCount($message["content"]);
        } else if ($message["role"] == "assistant" || $message["role"] == "user" || $message["role"] == "system") {
            $message["tokens"] = $this->util->tokenCount($message["content"]);
        } else {
            $message["tokens"] = 0;
        }
        $this->history[] = $message;
        file_put_contents($this->historyFile, json_encode($message) . "\n", FILE_APPEND);
    }

    public function getHistory($num_tokens)
    {
        if ($this->ash->debug) echo "debug: getHistory($num_tokens)\n";
        if ($this)
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
            }
        }
        if ($this->ash->debug) echo "debug: getHistory($num_tokens) results: " . count($result) . "\n";
        return array_reverse($result);
    }

    public function clearHistory()
    {
        if ($this->ash->debug) echo "debug: clearing history...\n";
        $this->history = [];
        file_put_contents($this->historyFile, "");
    }
}
