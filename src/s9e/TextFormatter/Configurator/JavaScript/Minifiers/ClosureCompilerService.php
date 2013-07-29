<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;

class ClosureCompilerService extends Minifier
{
	/**
	* @var string Closure Compiler's compilation level
	*/
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';

	/**
	* @var bool Whether to exclude Closure Compiler's default externs
	*/
	public $excludeDefaultExterns = true;

	/**
	* @var string Externs used for compilation
	*/
	public $externs;

	/**
	* @var string Closure Compiler Service's URL
	*/
	public $url = 'http://closure-compiler.appspot.com/compile';

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->externs = file_get_contents(__DIR__ . '/../externs.js');
	}

	/**
	* {@inheritdoc}
	*/
	public function getCacheDifferentiator()
	{
		$key = [$this->compilationLevel, $this->excludeDefaultExterns];

		if ($this->excludeDefaultExterns)
		{
			$key[] = $this->externs;
		}

		return $key;
	}

	/**
	* Compile given JavaScript source via the Closure Compiler Service
	*
	* @param  string $src JavaScript source
	* @return string      Compiled source
	*/
	public function minify($src)
	{
		$params = [
			'compilation_level' => $this->compilationLevel,
			'js_code'           => $src,
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		];

		// Add our custom externs if default externs are disabled
		if ($this->excludeDefaultExterns && $this->compilationLevel === 'ADVANCED_OPTIMIZATIONS')
		{
			$params['exclude_default_externs'] = 'true';
			$params['js_externs'] = $this->externs;
		}

		// Got to add dupe variables by hand
		$content = http_build_query($params) . '&output_info=errors&output_info=warnings';//&formatting=pretty_print';

		$response = json_decode(file_get_contents(
			$this->url,
			false,
			stream_context_create([
				'http' => [
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded",
					'content' => $content
				]
			])
		), true);

		if (isset($response['serverErrors'][0]))
		{
			$error = $response['serverErrors'][0];

			throw new RuntimeException('Server error ' . $error['code'] . ': ' . $error['error']);
		}

		if (isset($response['errors'][0]))
		{
			$error = $response['errors'][0];

			throw new RuntimeException('Compilation error: ' . $error['error']);
		}

		return $response['compiledCode'];
	}
}