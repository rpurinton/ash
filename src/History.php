<?php

namespace Rpurinton\Ash;

class History
{
    private $historyFile = "";
    private $history = [];

    public function __construct(private $util, private $ash)
    {
        $this->historyFile = __DIR__ . "/../history.d/history.jsonl";
        // if the directory doesn't exist create it then if the file doesn't exist create it
        if (!is_dir(__DIR__ . "/../history.d")) {
            mkdir(__DIR__ . "/../history.d", 0755, true);
        }
        $this->loadHistory();
    }

    public function __toArray()
    {
        return $this->history;
    }

    public function loadHistory()
    {
        if ($this->ash->debug) {
            echo ("debug: Loading history from " . $this->historyFile . "\n");
        }
        if (file_exists($this->historyFile)) {
            $history_jsonl = file_get_contents($this->historyFile);
            $history_jsonl = explode("\n", $history_jsonl);
            foreach ($history_jsonl as $history_json) {
                if ($history_json === "") {
                    continue;
                }
                $history = json_decode($history_json, true);
                if (!is_null($history)) {
                    $this->history[] = $history;
                }
            }
        } else {
            $this->history = [];
        }
        if ($this->ash->debug) {
            echo "debug: loaded messages: " . count($this->history) . "\n";
        }
    }

    public function saveMessage($message)
    {
        if ($this->ash->debug) {
            echo "debug: saving message: " . print_r($message, true) . "\n";
        }

        // Safely extract the content (default to "")
        $content = $message["content"] ?? "";

        if (isset($message["tool_calls"])) {
            if (!is_array($message["tool_calls"]) || !count($message["tool_calls"])) return;
            $arg_tokens = 0;
            foreach ($message["tool_calls"] as $tool_call) {
                $arguments = $tool_call["function"]["arguments"] ?? "";
                $arg_tokens += $this->util->tokenCount($arguments);
            }
            $message["tokens"] = $arg_tokens;
        } elseif (in_array($message["role"], ["assistant", "user", "system", "tool"])) {
            $message["tokens"] = $this->util->tokenCount($content);
        } else {
            $message["tokens"] = 0;
        }

        $this->history[] = $message;
        file_put_contents($this->historyFile, json_encode($message) . "\n", FILE_APPEND);
    }

    public function getHistory($num_tokens)
    {
        if ($this->ash->debug) {
            echo "debug: getHistory($num_tokens)\n";
        }
        $rev_history = array_reverse($this->history);
        $token_count = 0;
        $result = [];

        foreach ($rev_history as $message_id => $message) {
            $token_count += $message["tokens"];
            if ($token_count <= $num_tokens) {
                // Don’t include the “tokens” field in raw prompt data
                unset($message["tokens"]);
                $result[] = $message;
            } else {
                // If including this message would exceed our token limit, roll back
                $token_count -= $message["tokens"];
            }
        }

        if ($this->ash->debug) {
            echo "debug: getHistory($num_tokens) results: " . count($result) . "\n";
        }

        $fwd_history = array_reverse($result);
        $tool_calls = [];
        foreach ($fwd_history as $message_id => $message) {
            if (isset($message['tool_calls'])) {
                foreach ($message['tool_calls'] as $tool_call_message_index => $tool_call) {
                    $tool_calls[$tool_call['id']] = [$message_id, $tool_call_message_index];
                }
            }
            if ($message['role'] === 'tool') {
                $call_id = $message['tool_call_id'];
                if (!isset($tool_calls[$call_id])) {
                    unset($fwd_history[$message_id]);
                } else {
                    unset($tool_calls[$call_id]);
                }
            }
        }
        foreach ($tool_calls as $tool_call) {
            unset($fwd_history[$tool_call[0]]['tool_calls'][$tool_call[1]]);
        }
        foreach ($fwd_history as $message_id => $message) {
            if (isset($message['tool_calls']) && !count($message['tool_calls'])) {
                unset($fwd_history[$message_id]);
            }
        }

        return $fwd_history;
    }

    public function clearHistory()
    {
        if ($this->ash->debug) {
            echo "debug: clearing history...\n";
        }
        $this->history = [];
        file_put_contents($this->historyFile, "");
    }
}
