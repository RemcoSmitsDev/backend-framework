<?php

namespace Framework\Event;

use Framework\Event\Interfaces\BaseEventInterface;

abstract class BaseEvent implements BaseEventInterface
{
    abstract public function handle(BaseEventInterface $event, ?array $data);
}
