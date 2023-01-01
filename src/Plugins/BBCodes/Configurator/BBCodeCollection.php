<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;

class BBCodeCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	protected $onDuplicateAction = 'replace';

	/**
	* {@inheritdoc}
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("BBCode '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("BBCode '" . $key . "' does not exist");
	}

	/**
	* {@inheritdoc}
	*/
	public function normalizeKey($key)
	{
		return BBCode::normalizeName($key);
	}

	/**
	* {@inheritdoc}
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof BBCode)
		     ? $value
		     : new BBCode($value);
	}

	/**
	* {@inheritdoc}
	*
	* This method will remove redundant info such as the BBCode's tagName or defaultAttribute values
	* if they are the same as their default values
	*/
	public function asConfig()
	{
		$bbcodes = parent::asConfig();
		foreach ($bbcodes as $bbcodeName => &$bbcode)
		{
			// Remove the tag name if it's the same name as the BBCode
			if (isset($bbcode['tagName'])
			 && TagName::isValid($bbcodeName)
			 && TagName::normalize($bbcodeName) === $bbcode['tagName'])
			{
				unset($bbcode['tagName']);
			}

			// Remove the defaultAttribute name if it's the same name as the BBCode
			if (isset($bbcode['defaultAttribute'])
			 && AttributeName::isValid($bbcodeName)
			 && AttributeName::normalize($bbcodeName) === $bbcode['defaultAttribute'])
			{
				unset($bbcode['defaultAttribute']);
			}
		}
		unset($bbcode);

		return new Dictionary($bbcodes);
	}
}