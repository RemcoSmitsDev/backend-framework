<?php

namespace Framework\Debug;

use Curl\CaseInsensitiveArray;
use Curl\Curl;
use Framework\Content\Content;

class Debug
{
	/**
	 * Max distance before/after the error line
	 */
	const MAX_DISTANCE = 10;

	/**
	 * @var array
	 */
	public static array $data = [
		'errors' => [],
		'queries' => [],
		'requests' => [],
	];

	/**
	 * This method will append a information to the state
	 *
	 * @param  string $type
	 * @param  mixed  $data
	 * @return void
	 */
	public static function add(string $type, mixed $data): void
	{
		// appen data base on the type
		switch ($type) {
			case 'error':
				if (!is_array($data)) {
					$_data = $data;
					$data = [];
					$data['type'] = 'Exception';
					$data['data'] = $_data;
				}
				self::$data['errors'][] = $data;
				break;
			case 'query':
				self::$data['queries'][] = $data;
				break;
			case 'request':
				self::$data['requests'][] = $data;
				break;
		}
	}

	/**
	 * This method will render the debug screen if there where errors found
	 *
	 * @return void
	 */
	public static function render(): void
	{
		// stop when there where no errors found
		if (empty(self::$data['errors'])) return;

		// catch all content
		ob_get_clean();

		// get code preview
		$codePreview = self::getCodePreview(
			self::$data['errors'][0]['data']->getFile(),
			self::$data['errors'][0]['data']->getLine()
		);

		// show debug page
		app(new Content(realpath(__DIR__) . '/views/', 'debugViewLayout'))
			->template('debugViewTemplate', [
				'errors' => self::$data['errors'],
				'codepreview' => $codePreview,
				'queries' => self::$data['queries'],
				'requests' => self::$data['requests']
			])
			->listen();

		exit;
	}

	/**
	 * This method will code preview by a file + error line
	 *
	 * @param  string  $path
	 * @param  integer $line
	 * @return void
	 */
	public static function getCodePreview(string $path, int $line)
	{
		// open file
		$file = fopen($path, 'r');

		// check if can open file
		if (!$file) {
			// close file
			return fclose($file);
		}

		// keep track of lines information
		$lines = [
			'snippet' => '',
			'lineNumbers' => [],
			'line' => $line,
			'file' => $path
		];

		// keep track of line number
		$lineNumber = 1;

		// loop through all lines
		while (($l = fgets($file)) !== false) {
			// when is in range of line(where the error was from)
			if ($lineNumber >= ($line - self::MAX_DISTANCE) && $lineNumber <= ($line + self::MAX_DISTANCE)) {
				// decode line
				$l = htmlspecialchars($l);

				// get class name if is error line
				$errorClass = $line === $lineNumber ? 'bg-red-500/40 text-opacity-75' : '';

				// get url to the editor
				$url = self::getCodeEditorUrl($path, $lineNumber);

				// append to lines
				$lines['lineNumbers'][] = $lineNumber;
				$lines['snippet'] .= "<code-preview-line class=\"flex group leading-loose hover:bg-red-500/10 text-xs cursor-pointer {$errorClass}\" data-line=\"{$lineNumber}\" onclick=\"window.location.href='{$url}'\"><span class=\"pl-6\"><span>{$l}</span></span></code-preview-line>";
			}

			// check if is above the max distance
			if ($lineNumber > ($line + self::MAX_DISTANCE)) {
				break;
			}

			// increment lineNumber
			++$lineNumber;
		}

		// return lines information
		return $lines;
	}

	/**
	 * This method will format curl reqeust(for terminal)
	 *
	 * @param  string                     $url
	 * @param  array|CaseInsensitiveArray $headers
	 * @return string
	 */
	public static function formatCurlRequest(string $url, array|CaseInsensitiveArray $headers): string
	{
		// htmlspecialchars
		$url = htmlspecialchars($url);

		// start curl format
		$curlFormat = "curl '{$url}' \<br>";

		// loop through all the headers
		foreach ($headers as $key => $value) {
			$curlFormat .= htmlspecialchars("-H '{$key}: {$value}'") . ' \<br>';
		}

		// return formatted curl request
		return trim($curlFormat, ' \<br>') . ';';
	}

	/**
	 * This method will get the url to the editor(file, line)
	 *
	 * @param  string $path
	 * @param  string $line
	 * @return string
	 */
	public static function getCodeEditorUrl(string $path, string $line): string
	{
		return "vscode://file{$path}:{$line}";
	}

	/**
	 * This method will 
	 *
	 * @param  string $class
	 * @param  string $file
	 * @return string
	 */
	public static function chooseName(string $class, string $file): string
	{
		// explode class namespace
		$classParts = explode('\\', $class);

		// check if class is from file
		// then return class name(namespace)
		if (str_ends_with(str_replace('.php', '', $file), $classParts[count($classParts) - 1] ?? '')) {
			return $class;
		}
		// else return file(the file uses instance of the class)
		else {
			return $file;
		}
	}
}
