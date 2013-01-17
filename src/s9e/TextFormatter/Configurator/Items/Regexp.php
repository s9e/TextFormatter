<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp
{
	/**
	* @var bool Whether this regexp should become a JavaScript RegExp object with global flag
	*/
	protected $isGlobal;

	/**
	* @var string PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	*/
	protected $regexp;

	/**
	* Constructor
	*
	* @param  string $regexp PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	* @return void
	*/
	public function __construct($regexp, $isGlobal = false)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regular expression ' . var_export($regexp, true));
		}

		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}

	/**
	* Return this regexp as a string
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->regexp;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$regexp   = $this->regexp;
		$isGlobal = $this->isGlobal;

		$variant = new Variant($regexp);

		$variant->setDynamic(
			'JS',
			function () use ($regexp, $isGlobal)
			{
				$obj = RegexpConvertor::toJS($regexp);

				if ($isGlobal)
				{
					$obj->flags .= 'g';
				}

				return $obj;
			}
		);

		return $variant;
	}
}