<?php

namespace Framework\Debug;

use Framework\Interfaces\Debug\RayInterface;

class Ray implements RayInterface
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
    private mixed $data = [];

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

    /**
     * This method will set the type
     *
     * @param string $type
     * @return self
     */
    public function type(string $type): self
    {
        // set type
        $this->type = $type;

        // return self
        return $this;
    }

    /**
     * This method will set data to send to ray app
     *
     * @param array $data
     * @return self
     */
    public function data(array $data): self
    {
        foreach ($data as &$value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
        }
        $this->data = $data;

        return $this;
    }

    /**
     * This method will trigger fresh app
     *
     * @return self
     */
    public function fresh(): self
    {
        $this->fresh = true;

        return $this;
    }

    /**
     * This method start/stop measure
     *
     * @return self
     */
    public function measure(): self
    {
        // set type
        $this->type = 'measure';

        // memory formats
        $memoryUnit = [
            'Bytes',
            'KB',
            'MB',
            'GB',
            'TB',
            'PB'
        ];

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
            $this->measure['totalMemoryUsage'] = round(
                $this->measure['startMemory'] / pow(
                    1024,
                    ($x = floor(
                        log($this->measure['startMemory'], 1024)
                    ))
                ),
                2
            ) . ' ' . $memoryUnit[$x];
        }

        // return self
        return $this;
    }

    /**
     * $this method will set the color
     *
     * @param string $color
     * @return self
     */
    public function color(string $color): self
    {
        // set color
        $this->color = $color;

        // return self
        return $this;
    }

    /**
     * This method will set the title
     *
     * @param string $title
     * @return self
     */
    public function title(string $title): self
    {
        // set title
        $this->title = $title;

        // return self
        return $this;
    }

    private function buildDebugArray()
    {
        // get caller info
        array_shift($this->backtrace);

        // get caller
        $caller = $this->backtrace[array_key_last(
            array_filter($this->backtrace, function ($trace) {
                return isset($trace['file']);
            })
        )] ?? null;

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('Europe/Amsterdam'));

        // make data to send to application
        $debugData = [
            'id' => uniqid(strval(time() + random_int(1, 10000))),
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
                'xdebug' => []
            ],
            'fileName' => isset($caller['file']) ? basename($caller['file']) . ':' . $caller['line'] : '',
            'time' => $date->format('H:i:s'),
            'trace' => [
                'found' => !empty($this->backtrace),
                'data' => $this->backtrace
            ],
            'measure' => $this->measure,
            'enableAutoShow' => app()->getRaySettings()['enableAutoShow'],
            'host' => request()->server('HTTP_HOST')
        ];

        // loop trough data and format as dump
        foreach ($debugData['data']['original'] as $value) {
            ob_start();
            echo "<pre>";
            print_r($value);
            echo "</pre>";
            $debugData['data']['dd'][] = $this->type === 'query' ? htmlspecialchars_decode(ob_get_clean()) : ob_get_clean();

            // check if xdebug var_dump function exists
            if (function_exists('xdebug_var_dump') && $this->type !== 'query') {
                ob_start();
                xdebug_var_dump($value);
                $debugData['data']['xdebug'][] = ob_get_clean();
            }
        }

        // check if type is error
        if ($debugData['type'] === 'error') {
            // keep track of error previews
            $codePreviews = [];

            // loop through all errors
            foreach ($debugData['data']['original'] as $error) {
                // get error lines with format
                $codePreviews[] = $this->getErrorFileLines($error);
            }

            // add previews to data
            $debugData['data']['codePreviews'] = $codePreviews;
        }

        // reset values
        $this->reset($this->measure['done'] === true);

        // return debug array
        return $debugData;
    }

    /**
     * This function will send request to ray application
     * 
     * @return self
     */
    protected function send(): self
    {
        // send curl request without waiting for response
        exec("curl --location --request POST 'http://localhost:9890' -d '" . base64_encode(json_encode($this->buildDebugArray())) . "' > /dev/null &");

        // return self
        return $this;
    }

    /**
     * This function will format error file to lines with numbers
     *
     * @param array $error
     * @return string
     */
    private function getErrorFileLines(array $error)
    {
        // get code preview
        [$snippet, $lineNumbers, $line, $path] = Debug::getCodePreview($error['file'] ?? '', intval($error['line']));

        return '<div class="line-numbers">' . implode('<br>', $lineNumbers) . '</div><div class="code">' . $snippet . '</div>';
    }

    /**
     * This will reset all class properties
     *
     * @param boolean $canResetMeasure
     * @return void
     */
    private function reset(bool $canResetMeasure = false)
    {
        foreach ($this->defaultValues as $key => $value) {
            if ($key != 'measure' || $canResetMeasure) {
                $this->{$key} = $value;
            }
        }
    }
}