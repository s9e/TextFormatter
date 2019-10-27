<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons\Configurator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
class EmoticonCollection extends NormalizedCollection
{
	protected $onDuplicateAction = 'replace';
	public function normalizeValue($value)
	{
		return TemplateLoader::save(TemplateLoader::load($value));
	}
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Emoticon '" . $key . "' does not exist");
	}
}