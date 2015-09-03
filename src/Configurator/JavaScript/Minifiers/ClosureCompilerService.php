<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class ClosureCompilerService extends Minifier
{
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';
	public $excludeDefaultExterns = \true;
	public $externs;
	public $timeout = 10;
	public $url = 'http://closure-compiler.appspot.com/compile';
	public function __construct()
	{
		$this->externs = \file_get_contents(__DIR__ . '/../externs.service.js');
	}
	public function getCacheDifferentiator()
	{
		$key = array($this->compilationLevel, $this->excludeDefaultExterns);
		if ($this->excludeDefaultExterns)
			$key[] = $this->externs;
		return $key;
	}
	public function minify($src)
	{
		$params = array(
			'compilation_level' => $this->compilationLevel,
			'js_code'           => $src,
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		);
		if ($this->excludeDefaultExterns && $this->compilationLevel === 'ADVANCED_OPTIMIZATIONS')
		{
			$params['exclude_default_externs'] = 'true';
			$params['js_externs'] = $this->externs;
		}
		$content = \http_build_query($params) . '&output_info=errors';
		$response = \file_get_contents(
			$this->url,
			\false,
			\stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'timeout' => $this->timeout,
					'header'  => "Connection: close\r\nContent-length: " . \strlen($content) . "\r\nContent-type: application/x-www-form-urlencoded",
					'content' => $content
				)
			))
		);
		if (!$response)
			throw new RuntimeException('Could not contact the Closure Compiler service');
		$response = \json_decode($response, \true);
		if (\is_null($response))
		{
			$msgs = array(
					\JSON_ERROR_NONE => 'No error',
					\JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
					\JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
					\JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
					\JSON_ERROR_SYNTAX => 'Syntax error',
					\JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
				);
				throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . (isset($msgs[\json_last_error()]) ? $msgs[\json_last_error()] : 'Unknown error'));
		}
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