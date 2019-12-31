<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;

class EmoticonCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	protected $onDuplicateAction = 'replace';

	/**
	* Normalize an emoticon's template
	*
	* NOTE: this allows the HTML syntax to be used for individual emoticons
	*
	* @param  string $value Emoticon's original markup
	* @return string        Normalized template
	*/
	public function normalizeValue($value)
	{
		return TemplateLoader::save(TemplateLoader::load($value));
	}

	/**
	* {@inheritdoc}
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' does not exist");
	}
}