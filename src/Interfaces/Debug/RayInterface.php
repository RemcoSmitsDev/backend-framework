<?php

namespace Framework\Interfaces\Debug;

interface RayInterface
{
    /**
     * This method will set the type of the debug.
     *
     * @param string $type
     *
     * @return self
     */
    public function type(string $type): self;

    /**
     * This method will set the data to send to the debugging app.
     *
     * @param array $data
     *
     * @return self
     */
    public function data(array $data): self;

    /**
     * This method will fresh the debug app page.
     *
     * @return self
     */
    public function fresh(): self;

    /**
     * This method will start/stop measure performance.
     *
     * @return self
     */
    public function measure(): self;

    /**
     * This method will set the color for priority of debug item.
     *
     * @param string $color
     *
     * @return self
     */
    public function color(string $color): self;

    /**
     * This method will give an debug item an title for recognize easy debugging.
     *
     * @param string $title
     *
     * @return self
     */
    public function title(string $title): self;
}
