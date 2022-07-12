<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Client;

class Cached extends Client
{
	/**
	* @var Client
	*/
	public $client;

	/**
	* @var string
	*/
	public $cacheDir;

	/**
	* @param Client $client
	*/
	public function __construct(Client $client)
	{
		$this->client        = $client;
		$this->timeout       = $client->timeout;
		$this->sslVerifyPeer = $client->sslVerifyPeer;
	}

	/**
	* {@inheritdoc}
	*/
	public function get($url, array $options = [])
	{
		$filepath = $this->getCachedFilepath([$url, $options]);
		if (isset($filepath) && file_exists(preg_replace('(^compress\\.zlib://)', '', $filepath)))
		{
			return file_get_contents($filepath);
		}

		$content = $this->getClient()->get($url, $options);
		if (isset($filepath) && $content !== false)
		{
			file_put_contents($filepath, $content);
		}

		return $content;
	}

	/**
	* {@inheritdoc}
	*/
	public function post($url, array $options = [], $body = '')
	{
		return $this->getClient()->post($url, $options, $body);
	}

	/**
	* Generate and return a filepath that matches given vars
	*
	* @param  array  $vars
	* @return string
	*/
	protected function getCachedFilepath(array $vars)
	{
		if (!isset($this->cacheDir))
		{
			return null;
		}

		$filepath = $this->cacheDir . '/http.' . $this->getCacheKey($vars);
		if (extension_loaded('zlib'))
		{
			$filepath = 'compress.zlib://' . $filepath . '.gz';
		}

		return $filepath;
	}


	/**
	* Generate a key for a given set of values
	*
	* @param  string[] $vars
	* @return string
	*/
	protected function getCacheKey(array $vars)
	{
		return strtr(base64_encode(sha1(serialize($vars), true)), '/', '_');
	}

	/**
	* Return cached client configured with this client's options
	*
	* @return Client
	*/
	protected function getClient()
	{
		$this->client->timeout       = $this->timeout;
		$this->client->sslVerifyPeer = $this->sslVerifyPeer;

		return $this->client;
	}
}