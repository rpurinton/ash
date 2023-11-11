<?php

namespace Rpurinton\Ash;

class Util
{
    private $encoder = null;

    public function __construct()
    {
        $this->encoder = new \TikToken\Encoder();
    }

    public function tokenCount($input)
    {
        return count($this->encoder->encode($input));
    }
}
