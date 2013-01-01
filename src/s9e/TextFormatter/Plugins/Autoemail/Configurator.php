<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoemail;

use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute that stores the link's URL
	*/
	protected $attrName = 'email';

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '@';

	/**
	* {@inheritdoc}
	*/
	protected $regexp = '/\\b[-a-z0-9_+.]+@[-a-z0-9.]+/Si';

	/**
	* @var string Name of the tag used to represent links
	*/
	protected $tagName = 'EMAIL';

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	public function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Add an attribute using the #email filter
		$tag->attributes->add($this->attrName)->filterChain->append('#email');

		// Set the default template
		$tag->defaultTemplate
			= '<a href="mailto:{@' . $this->attrName . '}"><xsl:apply-templates/></a>';
	}

	/**
	* Change the attribute name used by this plugin
	*
	* @param  string $attrName New attribute name
	* @return void
	*/
	protected function setAttrName($attrName)
	{
		$this->attrName = AttributeName::normalize($attrName);
	}

	/**
	* Change the tag name used by this plugin
	*
	* @param  string $tagName New tag name
	* @return void
	*/
	protected function setTagName($tagName)
	{
		$this->tagName = TagName::normalize($tagName);
	}
}