<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;

class BBCodeCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return BBCode::normalizeName($key);
	}

	public function normalizeValue($value)
	{
		return ($value instanceof BBCode)
		     ? $value
		     : new BBCode($value);
	}

	public function asConfig()
	{
		$config = parent::asConfig();

		foreach ($config as $bbcodeName => &$bbcode)
		{
			if (isset($bbcode['tagName'])
			 && TagName::isValid($bbcodeName)
			 && TagName::normalize($bbcodeName) === $bbcode['tagName'])
				unset($bbcode['tagName']);

			if (isset($bbcode['defaultAttribute'])
			 && AttributeName::isValid($bbcodeName)
			 && AttributeName::normalize($bbcodeName) === $bbcode['defaultAttribute'])
				unset($bbcode['defaultAttribute']);
		}
		unset($bbcode);

		return $config;
	}
}