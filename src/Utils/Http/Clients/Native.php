<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
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
	public function get($url, array $options = [])
	{
		return $this->request('GET', $url, $options);
	}

	/**
	* {@inheritdoc}
	*/
	public function post($url, array $options = [], $body = '')
	{
		return $this->request('POST', $url, $options, $body);
	}

	/**
	* Create a stream context for given request
	*
	* @param  string   $method  Request method
	* @param  array    $options Request options
	* @param  string   $body    Request body
	* @return resource
	*/
	protected function createContext($method, array $options, $body)
	{
		$contextOptions = [
			'ssl'  => ['verify_peer' => $this->sslVerifyPeer],
			'http' => [
				'method'  => $method,
				'timeout' => $this->timeout,
				'header'  => $this->generateHeaders($options, $body),
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
	* @param  array    $options Request options
	* @param  string   $body    Request body
	* @return string[]
	*/
	protected function generateHeaders(array $options, $body)
	{
		$options += ['headers' => []];
		if ($this->gzipEnabled)
		{
			$options['headers'][] = 'Accept-Encoding: gzip';
		}
		$options['headers'][] = 'Content-Length: ' . strlen($body);

		return $options['headers'];
	}

	/**
	* Execute an HTTP request
	*
	* @param  string      $method  Request method
	* @param  string      $url     Request URL
	* @param  array       $options Request options
	* @param  string      $body    Request body
	* @return string|bool          Response body or FALSE
	*/
	protected function request($method, $url, array $options, $body = '')
	{
		$response = @file_get_contents($url, false, $this->createContext($method, $options, $body));
		if ($response === false)
		{
			return false;
		}

		$response = $this->decompress($response);
		if (!empty($options['returnHeaders']))
		{
			$response = implode("\r\n", $http_response_header) . "\r\n\r\n" . $response;
		}

		return $response;
	}
}