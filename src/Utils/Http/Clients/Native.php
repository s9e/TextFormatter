<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Client;

class Native extends Client
{
	/**
	* @var bool Whether to use gzip encoding
	*/
	public $gzipEnabled;

	/**
	* Constructor
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
	public function post($url, $headers = [], $body = '')
	{
		return $this->request('POST', $url, $headers, $body);
	}

	/**
	* Create a stream context for given request
	*
	* @param  string   $method  Request method
	* @param  string[] $headers Request headers
	* @param  string   $body    Request body
	* @return resource
	*/
	protected function createContext($method, array $headers, $body)
	{
		$contextOptions = [
			'ssl'  => ['verify_peer' => $this->sslVerifyPeer],
			'http' => [
				'method'  => $method,
				'timeout' => $this->timeout,
				'header'  => $this->generateHeaders($headers, $body),
				'content' => $body
			]
		];

		return stream_context_create($contextOptions);
	}

	/**
	* Decompress given page if applicable
	*
	* @param  string $content Response body, potentially compressed
	* @return string          Response body, uncompressed
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
	* Generate a list of headers for given request
	*
	* @param  string[] $headers Request headers
	* @param  string   $body    Request body
	* @return string[]
	*/
	protected function generateHeaders(array $headers, $body)
	{
		if ($this->gzipEnabled)
		{
			$headers[] = 'Accept-Encoding: gzip';
		}
		$headers[] = 'Content-Length: ' . strlen($body);

		return $headers;
	}

	/**
	* Execute an HTTP request
	*
	* @param  string      $method  Request method
	* @param  string      $url     Request URL
	* @param  string[]    $headers Request headers
	* @return string|bool          Response body or FALSE
	*/
	protected function request($method, $url, $headers, $body = '')
	{
		$response = @file_get_contents($url, false, $this->createContext($method, $headers, $body));

		return (is_string($response)) ? $this->decompress($response) : $response;
	}
}