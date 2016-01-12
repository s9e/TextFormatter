<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers\Http\Clients;

use s9e\TextFormatter\Configurator\Helpers\Http\Clients\Curl;

class CurlTest extends AbstractTest
{
	protected function getInstance()
	{
		return new Curl;
	}
}