<?php

namespace Rpurinton\Ash;

class Util
{
    private $encoder = null;

    public function __construct()
    {
        ini_set('memory_limit', '512M');
        $this->encoder = new \TikToken\Encoder();
    }

    public function tokenCount($input)
    {
        try {
            $count = count($this->encoder->encode($input));
        } catch (\Exception $e) {
            echo ("(ash) Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Error $e) {
            echo ("(ash) Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Throwable $e) {
            echo ("(ash) Error: " . print_r($e, true) . "\n");
            $count = 0;
        }
        return $count;
    }
}
