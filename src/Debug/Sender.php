#!/usr/local/bin/php
<?php

use Framework\Database\SqlFormatter;

// require vendor
require_once(__DIR__ . '/../../vendor/autoload.php');

function getErrorFileLines(array $error)
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
		$strLineNumbers .= "<div>$i</div>";
	}

	// slice lines
	$lines = array_slice(file($error['file']), $start, $end);

	// loop trough all lines
	foreach ($lines as $key => $line) {
		// make sure that html characters are escaped
		// $line = clearInjections($line);

		// check if is current error line(place where the error was througn)
		if (($start + $key + 1) === $errorLine) {
			$str .= '<div class="line error"><span>' . preg_replace("/\t/", '&nbsp&nbsp&nbsp&nbsp&nbsp', $line) . '</span></div>';
		} else {
			$str .= '<div class="line"><span>' . preg_replace("/\t/", '&nbsp&nbsp&nbsp&nbsp&nbsp', $line) . '</span></div>';
		}

		// check if end was reached
		if ($start + $key === $end) {
			break;
		}
	}

	return '<div class="line-numbers">' . $strLineNumbers . '</div><div class="code">' . $str . '</div>';
}

// get data
$data = unserialize(base64_decode($argv[1]));

// loop trough data and format as dump
foreach ($data['data']['original'] as $value) {
	ob_start();
	dd($value);
	$data['data']['dd'][] = ob_get_clean();
}

// check if type is error
if ($data['type'] === 'error') {
	// keep track of error previews
	$codePreviews = [];

	// loop through all errors
	foreach ($data['data']['original'] as $error) {
		// get error lines with format
		$codePreviews[] = getErrorFileLines($error);
	}

	// add previews to data
	$data['data']['codePreviews'] = $codePreviews;
}

// send request to ray application
Http()->post('http://localhost:9890', json_encode($data));
