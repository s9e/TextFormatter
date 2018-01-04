<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\OnlineMinifier;
class ClosureCompilerService extends OnlineMinifier
{
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';
	public $excludeDefaultExterns = \true;
	public $externs;
	public $url = 'https://closure-compiler.appspot.com/compile';
	public function __construct()
	{
		parent::__construct();
		$this->externs = \file_get_contents(__DIR__ . '/../externs.service.js');
	}
	public function getCacheDifferentiator()
	{
		$key = [$this->compilationLevel, $this->excludeDefaultExterns];
		if ($this->excludeDefaultExterns)
			$key[] = $this->externs;
		return $key;
	}
	public function minify($src)
	{
		$body     = $this->generateRequestBody($src);
		$response = $this->query($body);
		if ($response === \false)
			throw new RuntimeException('Could not contact the Closure Compiler service');
		return $this->decodeResponse($response);
	}
	protected function decodeResponse($response)
	{
		$response = \json_decode($response, \true);
		if (\is_null($response))
			throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . \json_last_error_msg());
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
	protected function generateRequestBody($src)
	{
		$params = [
			'compilation_level' => $this->compilationLevel,
			'js_code'           => $src,
			'output_format'     => 'json',
			'output_info'       => 'compiled_code'
		];
		if ($this->excludeDefaultExterns && $this->compilationLevel === 'ADVANCED_OPTIMIZATIONS')
		{
			$params['exclude_default_externs'] = 'true';
			$params['js_externs'] = $this->externs;
		}
		$body = \http_build_query($params) . '&output_info=errors';
		return $body;
	}
	protected function query($body)
	{
		return $this->httpClient->post(
			$this->url,
			['Content-Type: application/x-www-form-urlencoded'],
			$body
		);
	}
}