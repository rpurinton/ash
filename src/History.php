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

        // Assign token counts based on the role and typical data in the message.
        if ($message["role"] === "assistant" && isset($message["function_call"])) {
            // Old function-call style
            $message["tokens"] = $this->util->tokenCount($message["function_call"]["arguments"]);
        } else if ($message["role"] === "function") {
            // Legacy approach
            $message["tokens"] = $this->util->tokenCount($message["content"]);
        } else if ($message["role"] === "tool") {
            // New tool result: the content is typically the tool's response
            $message["tokens"] = $this->util->tokenCount($message["content"]);
        } else if (in_array($message["role"], ["assistant", "user", "system"])) {
            // Normal text from assistant, user, or system
            $message["tokens"] = $this->util->tokenCount($message["content"]);
        } else {
            // Fallback: unknown or custom role
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

        foreach ($rev_history as $message) {
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
        return array_reverse($result);
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
