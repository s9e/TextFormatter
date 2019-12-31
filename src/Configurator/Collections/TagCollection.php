<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Validators\TagName;

class TagCollection extends NormalizedCollection
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
		return new RuntimeException("Tag '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Tag '" . $key . "' does not exist");
	}

	/**
	* Normalize a tag name used as a key in this colelction
	*
	* @param  string $key Original name
	* @return string      Normalized name
	*/
	public function normalizeKey($key)
	{
		return TagName::normalize($key);
	}

	/**
	* Normalize a value to an instance of Tag
	*
	* @param  array|null|Tag $value
	* @return Tag
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Tag)
		     ? $value
		     : new Tag($value);
	}
}