<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use s9e\TextFormatter\ConfigBuilder\Traits\Configurable;
use s9e\TextFormatter\ConfigBuilder\Validators\AttributeName;
use s9e\TextFormatter\ConfigBuilder\Validators\TagName;

class BBCode
{
	use Configurable;

	/**
	* @var array List of attributes whose value is to be made the content between the BBCode's tags
	*            if it's not explicitly given
	*/
	protected $contentAttributes = array();

	/**
	* @var string Name of the default attribute
	*/
	protected $defaultAttribute;

	/**
	* @var string Name of the tag used to represent this BBCode in the intermediate representation
	*/
	protected $tagName;

	/**
	* Normalize the name of a BBCode
	*
	* @param  string $bbcodeName Original name
	* @return string             Normalized name
	*/
	public static function normalizeName($bbcodeName)
	{
		if (!preg_match('#^(?:[a-z][a-z_0-9]*|\\*)$#Di', $bbcodeName))
		{
			throw new InvalidArgumentException ("Invalid BBCode name '" . $bbcodeName . "'");
		}

		return strtoupper($bbcodeName);
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