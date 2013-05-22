<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Escaper;

use DOMDocument;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '\\';

	/**
	* @var string Regexp that matches one backslash and one single Unicode character
	*/
	protected $regexp = '#\\\\.#us';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'ESC';

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->rules->denyAll();
		$tag->defaultTemplate = '<xsl:value-of select="substring(.,2)"/>';
	}
}