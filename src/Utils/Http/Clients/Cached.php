<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\Http\Clients;
use s9e\TextFormatter\Utils\Http\Client;
class Cached extends Client
{
	public $client;
	public $cacheDir;
	public function __construct(Client $client)
	{
		$this->client        = $client;
		$this->timeout       = $client->timeout;
		$this->sslVerifyPeer = $client->sslVerifyPeer;
	}
	public function get($url, $headers = [])
	{
		$filepath = $this->getCachedFilepath([$url, $headers]);
		if (isset($filepath) && \file_exists(\preg_replace('(^compress\\.zlib://)', '', $filepath)))
			return \file_get_contents($filepath);
		$content = $this->getClient()->get($url, $headers);
		if (isset($filepath) && $content !== \false)
			\file_put_contents($filepath, $content);
		return $content;
	}
	public function post($url, $headers = [], $body = '')
	{
		return $this->getClient()->post($url, $headers, $body);
	}
	protected function getCachedFilepath(array $vars)
	{
		if (!isset($this->cacheDir))
			return \null;
		$filepath = $this->cacheDir . '/http.' . $this->getCacheKey($vars);
		if (\extension_loaded('zlib'))
			$filepath = 'compress.zlib://' . $filepath . '.gz';
		return $filepath;
	}

	protected function getCacheKey(array $vars)
	{
		return \strtr(\base64_encode(\sha1(\serialize($vars), \true)), '/', '_');
	}
	protected function getClient()
	{
		$this->client->timeout       = $this->timeout;
		$this->client->sslVerifyPeer = $this->sslVerifyPeer;
		return $this->client;
	}
}