<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils\Http;

abstract class Client
{
	/**
	* @var bool Whether to verify the peer's SSL certificate
	*/
	public $sslVerifyPeer = false;

	/**
	* @var integer Request timeout
	*/
	public $timeout = 10;

	/**
	* Execute a GET request and return the response's body
	*
	* @param  string      $url     Request URL
	* @param  array       $options Request options
	* @return string|bool          Response content or FALSE
	*/
	abstract public function get($url, array $options = []);

	/**
	* Execute a POST request and return the response's body
	*
	* @param  string      $url     Request URL
	* @param  array       $options Request options
	* @param  string      $body    Request body
	* @return string|bool          Response content or FALSE
	*/
	abstract public function post($url, array $options = [], $body = '');
}