<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLComments;

use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'content';

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '<!--';

	/**
	* @var string Regexp that matches comments
	*/
	protected $regexp = '/<!--(?!\\[if).*?-->/is';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'HC';

	/**
	* {@inheritdoc}
	*/
	protected function setUp()
	{
		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName);
		$tag->rules->ignoreTags();
		$tag->template = '<xsl:comment><xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/></xsl:comment>';
	}
}