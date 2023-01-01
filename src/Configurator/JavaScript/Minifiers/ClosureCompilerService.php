<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\OnlineMinifier;

class ClosureCompilerService extends OnlineMinifier
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
	public $url = 'https://closure-compiler.appspot.com/compile';

	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct();
		$this->externs = file_get_contents(__DIR__ . '/../externs.service.js');
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
		$body     = $this->generateRequestBody($src);
		$response = $this->query($body);
		if ($response === false)
		{
			throw new RuntimeException('Could not contact the Closure Compiler service');
		}

		return $this->decodeResponse($response);
	}

	/**
	* Decode the response returned by the Closure Compiler service
	*
	* @param  string $response Response body
	* @return string           Minified code
	*/
	protected function decodeResponse($response)
	{
		$response = json_decode($response, true);
		if (is_null($response))
		{
			throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . json_last_error_msg());
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

	/**
	* Generate the request body for given code
	*
	* @param  string $src JavaScript source
	* @return string      Compiled source
	*/
	protected function generateRequestBody($src)
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

		// Add dupe variables by hand
		$body = http_build_query($params) . '&output_info=errors';

		return $body;
	}

	/**
	* Query the Closure Compiler service
	*
	* @param  string       $body Request body
	* @return string|false       Response body, or FALSE
	*/
	protected function query($body)
	{
		return $this->httpClient->post(
			$this->url,
			['headers' => ['Content-Type: application/x-www-form-urlencoded']],
			$body
		);
	}
}