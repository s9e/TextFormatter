<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeList;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;

/**
* @property AttributeList $contentAttributes List of attributes whose value is to be made the content between the BBCode's tags if it's not explicitly given
* @property string $defaultAttribute Name of the default attribute
* @property bool $forceLookahead Whether the parser should look ahead in the text for an end tag before adding a start tag
* @property string $tagName Name of the tag used to represent this BBCode in the intermediate representation
*/
class BBCode implements ConfigProvider
{
	use Configurable;

	/**
	* @var AttributeList List of attributes whose value is to be made the content between the
	*                    BBCode's tags if it's not explicitly given
	*/
	protected $contentAttributes;

	/**
	* @var string Name of the default attribute
	*/
	protected $defaultAttribute;

	/**
	* @var bool Whether the parser should look ahead in the text for an end tag before adding a
	*           start tag
	*/
	protected $forceLookahead = false;

	/**
	* @var string Name of the tag used to represent this BBCode in the intermediate representation
	*/
	protected $tagName;

	/**
	* @param array $options This BBCode's options
	*/
	public function __construct(array $options = null)
	{
		$this->contentAttributes = new AttributeList;
		if (isset($options))
		{
			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = ConfigHelper::toArray(get_object_vars($this));
		if (!$this->forceLookahead)
		{
			unset($config['forceLookahead']);
		}

		return $config;
	}

	/**
	* Normalize the name of a BBCode
	*
	* Follows the same rules as tag names with one exception: "*" is kept for compatibility with
	* other BBCode engines
	*
	* @param  string $bbcodeName Original name
	* @return string             Normalized name
	*/
	public static function normalizeName($bbcodeName)
	{
		if ($bbcodeName === '*')
		{
			return '*';
		}

		if (strpos($bbcodeName, ':') !== false || !TagName::isValid($bbcodeName))
		{
			throw new InvalidArgumentException("Invalid BBCode name '" . $bbcodeName . "'");
		}

		return TagName::normalize($bbcodeName);
	}

	/**
	* Set the default attribute name for this BBCode
	*
	* @param string $attrName
	*/
	public function setDefaultAttribute($attrName)
	{
		$this->defaultAttribute = AttributeName::normalize($attrName);
	}

	/**
	* Set the tag name that represents this BBCode in the intermediate representation
	*
	* @param string $tagName
	*/
	public function setTagName($tagName)
	{
		$this->tagName = TagName::normalize($tagName);
	}
}