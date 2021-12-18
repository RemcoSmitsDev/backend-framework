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

    /**
     * This method will set the type
     *
     * @param string $type
     * @return void
     */
    public function type(string $type)
    {
        // set type
        $this->type = $type;

        // return self
        return $this;
    }

    /**
     * This method will set data to send to ray app
     *
     * @param mixed $data
     * @return self
     */
    public function data(mixed $data): self
    {
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
     * @return void
     */
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

    /**
     * This function will send request to ray application
     * 
     * @return self
     */
    public function send(): self
    {
        // get caller info
        array_shift($this->backtrace);

        // get caller
        $caller = $this->backtrace[array_key_last(
            array_filter($this->backtrace, function ($trace) {
                return isset($trace['file']);
            })
        )] ?? null;

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
            'time' => strval(gmdate("H:i:s", time())),
            'trace' => [
                'found' => !empty($this->backtrace),
                'data' => $this->backtrace
            ],
            'measure' => $this->measure,
            'host' => HTTP_HOST
        ];

        // reset values
        $this->reset($this->measure['done'] === true);

        // loop trough data and format as dump
        foreach ($debugData['data']['original'] as $value) {
            ob_start();
            dd($value);
            $debugData['data']['dd'][] = ob_get_clean();
        }

        // check if xdebug var_dump function exists
        if (function_exists('xdebug_var_dump') && $this->type !== 'query') {
            foreach ($debugData['data']['original'] as $value) {
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

        // send request to ray application
        http()->post('http://localhost:9890', json_encode($debugData));

        // return self
        return $this;
    }

    /**
     * This function will format error file to lines with numbers
     *
     * @param array $error
     * @return void
     */
    public function getErrorFileLines(array $error)
    {
        // calc start line
        $errorLine = intval($error['line']);
        $start = $errorLine - 10;
        $end = $errorLine + 10;

        // check if start line need to get changed
        if ($errorLine <= 10) {
            $start = 1;
        }

        // keep track of all information
        $str = '';
        $strLineNumbers = '';

        // make linenumbers string(sidebar)
        for ($i = $start + 1; $i <= $end; $i++) {
            $strLineNumbers .= "<div>{$i}</div>";
        }

        // slice lines
        $lines = array_slice(file($error['file']), $start, $end);

        // loop trough all lines
        foreach ($lines as $key => $line) {
            // check if is current error line(place where the error was througn)
            if (($start + $key + 1) === $errorLine) {
                $str .= '<div class="line error"><span>' . htmlspecialchars(preg_replace("/\t/", '&nbsp&nbsp&nbsp&nbsp&nbsp', $line)) . '</span></div>';
            } else {
                $str .= '<div class="line"><span>' . htmlspecialchars(preg_replace("/\t/", '&nbsp&nbsp&nbsp&nbsp&nbsp', $line)) . '</span></div>';
            }

            // check if end was reached
            if ($start + $key === $end - 1) {
                break;
            }
        }

        return '<div class="line-numbers">' . $strLineNumbers . '</div><div class="code">' . $str . '</div>';
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
