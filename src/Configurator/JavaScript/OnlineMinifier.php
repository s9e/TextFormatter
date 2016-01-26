<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use s9e\TextFormatter\Utils\Http;

abstract class OnlineMinifier extends Minifier
{
	/**
	* @var \s9e\TextFormatter\Utils\Http\Client Client used to perform HTTP request
	*/
	public $client;

	/**
	* @var integer Timeout in seconds
	*/
	public $timeout = 10;

	/**
	* Return a cached instance of the HTTP client
	*
	* @return \s9e\TextFormatter\Utils\Http\Client
	*/
	protected function getHttpClient()
	{
		if (!isset($this->client))
		{
			$this->client = Http::getClient();
		}
		$this->client->timeout = $this->timeout;

		return $this->client;
	}
}