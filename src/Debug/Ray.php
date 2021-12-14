<?php

namespace Framework\Debug;


class Ray
{
    /**
     * keep track of default propery values
     */
    private ?array $defaultValues;

    /**
     * Keep track of debug type (for the ui in the app)
     */
    public string $type = 'normal';

    /**
     * green | orange | red
     */
    private string $color = '';

    /**
     * Title for inside ray app
     */
    private string $title = '';

    /**
     * Data that will be displayed
     */
    public mixed $data = [];

    /**
     * Show an fresh page
     */
    private bool $fresh = false;

    /**
     * This will be displayed when measure was ended
     */
    public array $measure = [
        'startTime' => 0,
        'endTime' => 0,
        'totalExecutionTime' => 0,
        'startMemory' => 0,
        'peekMemory' => 0,
        'totalMemoryUsage' => 0,
        'done' => false
    ];

    /**
     * 
     */
    protected array $backtrace = [];

    public function __construct()
    {
        $this->defaultValues = get_object_vars($this);
    }

    public function type(string $type)
    {
        // set type
        $this->type = $type;

        // return self
        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function fresh(): self
    {
        $this->fresh = true;

        return $this;
    }

    public function measure()
    {
        // set type
        $this->type = 'measure';

        // memory formats
        $memoryUnit = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB');

        // set measure time
        if (!$this->measure['startTime']) {
            // set data
            $this->measure['startTime'] = microtime(true) * 100;
            $this->measure['startMemory'] = memory_get_usage();
            // send messages
            $this->title('Start measuring performance...');
        } else {
            $this->measure['endTime'] = microtime(true) * 100;
            $this->measure['peekMemory'] = memory_get_peak_usage();
            $this->measure['done'] = true;

            $this->measure['totalExecutionTime'] = ceil($this->measure['endTime'] - $this->measure['startTime']) / 100;
            $this->measure['totalMemoryUsage'] = round($this->measure['startMemory'] / pow(1024, ($x = floor(log($this->measure['startMemory'], 1024)))), 2) . ' ' . $memoryUnit[$x];
        }

        // return self
        return $this;
    }

    public function color(string $color): self
    {
        // set color
        $this->color = $color;

        // return self
        return $this;
    }

    public function title(string $title): self
    {
        // set title
        $this->title = $title;

        // return self
        return $this;
    }

    public function send()
    {
        // get caller info
        array_shift($this->backtrace);

        // get caller
        $caller = $this->backtrace[array_key_last(array_filter($this->backtrace, function ($trace) {
            return isset($trace['file']);
        }))] ?? null;

        // keep track of xdebug data if extention exists
        $xdebug = [];

        // check if xdebug var_dump function exists
        if (function_exists('xdebug_var_dump') && $this->type !== 'query') {
            foreach ($this->data as $value) {
                ob_start();
                xdebug_var_dump($value);
                $xdebug[] = ob_get_clean();
            }
        }

        // make data to send to application
        $debugData = [
            'id' => time() + random_int(1, 10000),
            'type' => $this->type,
            'fresh' => $this->fresh,
            'title' => $this->title,
            'color' => $this->color,
            'data' => [
                'found' => !empty($this->data),
                'request' => [
                    'GET' => (array) request()->get(),
                    'POST' => (array) request()->post(),
                    'FILE' => (array) request()->file()
                ],
                'original' => $this->data,
                'dd' => [],
                'xdebug' => $xdebug
            ],
            'fileName' => isset($caller['file']) ? basename($caller['file']) . ':' . $caller['line'] : '',
            'time' => strval(gmdate("H:i:s", time())),
            'trace' => [
                'found' => !empty($this->backtrace),
                'data' => json_encode($this->backtrace)
            ],
            'measure' => $this->measure
        ];

        // reset values
        $this->reset($this->measure['done'] === true);

        // exec send to ray application
        exec('php ' . __DIR__ . '/Sender.php ' . base64_encode(serialize($debugData)), $output);

        dd($output);

        // return self
        return $this;
    }

    private function reset(bool $canResetMeasure = false)
    {
        foreach ($this->defaultValues as $key => $value) {
            if ($key != 'measure' || $canResetMeasure) {
                $this->{$key} = $value;
            }
        }
    }
}
