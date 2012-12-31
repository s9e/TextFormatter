<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript\Minifiers;

use s9e\TextFormatter\Configurator\Javascript\Minifier;

class ClosureCompilerService implements Minifier
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
	* Compile given Javascript source via the Closure Compiler Service
	*
	* @param  string $src Javascript source
	* @return string      Compiled source
	*/
	public function minify($src)
	{
		$params = array(
			'compilation_level' => $this->compilationLevel,
			'js_code'           => $src,
			'js_externs'        => $this->externs,
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		);

		if ($this->excludeDefaultExterns)
		{
			$params['exclude_default_externs'] = 'true';
		}

		// Got to add dupe variables by hand
		$content = http_build_query($params) . '&output_info=errors';

		$response = json_decode(file_get_contents(
			$this->url,
			false,
			stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded",
					'content' => $content
				)
			))
		), true);

		return $response['compiledCode'];
	}
}