<?php

namespace Framework\Chain;

class Chain
{
    private $returnValue;
    private $callback;

    public function __construct($returnValue, $callback)
    {
        $this->returnValue = $returnValue;
        $this->callback = $callback;
    }

    public function chain()
    {
        return $this->returnValue;
    }

    public function __destruct()
    {
        // check if callback exists
        if (!$this->callback) {
            return false;
        }
        // set callback to var
        $callback = $this->callback;

        // set callback to null
        $this->callback = null;

        // call callback
        ($callback)();
    }
}
