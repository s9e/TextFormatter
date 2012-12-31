<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;

/**
* This class is meant to act as a placeholder wherever a callback is expected but never invoked.
* It is automatically replaced by the value passed to the constructor
*/
class CallbackPlaceholder implements ConfigProvider
{
	/**
	* @var mixed
	*/
	protected $configValue;

	/**
	* Constructor
	*
	* @param mixed $configValue
	*/
	public function __construct($configValue)
	{
		$this->configValue = $configValue;
	}

	/**
	* @throws RuntimeException
	*/
	public function __invoke()
	{
		throw new RuntimeException('CallbackPlaceholder is not meant to be invoked');
	}

	/**
	* Return the configuration this object acts as a placeholder for
	*
	* @return mixed
	*/
	public function asConfig()
	{
		return $this->configValue;
	}
}