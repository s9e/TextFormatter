<?php

namespace s9e\TextFormatter\Tests\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Clients\Curl;

class CurlTest extends AbstractTest
{
	protected function getInstance()
	{
		return new Curl;
	}
}