<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript\Minifiers;

class ClosureCompilerService implements Minifier
{
	/**
	* @var 
	*/
	protected $url = 'http://closure-compiler.appspot.com/compile';

	/**
	* Compile given Javascript source via the Closure Compiler Service
	*
	* @param  string $src Javascript source
	* @return string      Compiled source
	*/
	public function minify($src)
	{
		$content = http_build_query(array(
			'compilation_level' => 'ADVANCED_OPTIMIZATIONS',
			'exclude_default_externs' => 'true',
			'js_code'           => $src,
			'js_externs'        => file_get_contents(__DIR__ . '/../externs.js'),
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		));

		// Got to add dupe variables by hand
		$content .= '&output_info=errors';

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
	}
}