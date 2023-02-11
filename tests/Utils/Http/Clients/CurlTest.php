<?php

namespace s9e\TextFormatter\Tests\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Clients\Curl;

class CurlTest extends AbstractTestClass
{
	/**
	* @beforeClass
	*/
	public static function removeCachedHandle()
	{
		HandleRemover::removeHandle();
	}

	protected function getInstance()
	{
		return new Curl;
	}
}

class HandleRemover extends Curl
{
	public static function removeHandle()
	{
		self::$handle = null;
	}
}