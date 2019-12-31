<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use s9e\TextFormatter\Utils\Http;

abstract class OnlineMinifier extends Minifier
{
	/**
	* @var \s9e\TextFormatter\Utils\Http\Client Client used to perform HTTP request
	*/
	public $httpClient;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->httpClient = Http::getClient();
	}
}