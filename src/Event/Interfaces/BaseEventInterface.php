<?php

namespace Framework\Event\Interfaces;

interface BaseEventInterface
{
    /**
     * @param BaseEventInterface $event
     * @param array|null         $data
     *
     * @return void
     */
    public function handle(BaseEventInterface $event, ?array $data);
}
