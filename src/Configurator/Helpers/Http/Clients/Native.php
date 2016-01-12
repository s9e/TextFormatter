<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\Http\Clients;

use s9e\TextFormatter\Configurator\Helpers\Http\Client;

class Native extends Client
{
	/**
	* @var bool Whether to use gzip encoding
	*/
	public $gzipEnabled;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->gzipEnabled = extension_loaded('zlib');
	}

	/**
	* {@inheritdoc}
	*/
	public function get($url, $headers = [])
	{
		return $this->request('GET', $url, $headers);
	}

	/**
	* {@inheritdoc}
	*/
	public function post($url, $headers = [], $body = null)
	{
		return $this->request('POST', $url, $headers, $body);
	}

	/**
	* Decompress given page if applicable
	*
	* @param  string|bool $content Request's response body or FALSE
	* @return string|bool
	*/
	protected function decompress($content)
	{
		if ($this->gzipEnabled && substr($content, 0, 2) === "\x1f\x8b")
		{
			return gzdecode($content);
		}

		return $content;
	}

	/**
	* Execute an HTTP request
	*
	* @param  string      $method  Request method
	* @param  string      $url     Request URL
	* @param  string[]    $headers Request headers
	* @return string|bool          Response body or FALSE
	*/
	protected function request($method, $url, $headers, $body = null)
	{
		$contextOptions = ['http' => ['method' => $method]];
		if ($this->gzipEnabled)
		{
			$headers[] = 'Accept-Encoding: gzip';
		}
		if (isset($body))
		{
			$headers[] = 'Content-Length: ' . strlen($body);
			$contextOptions['http']['content'] = $body;
		}
		if (!empty($headers))
		{
			$contextOptions['http']['header'] = $headers;
		}

		return $this->decompress(@file_get_contents($url, false, stream_context_create($contextOptions)));
	}
}