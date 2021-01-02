<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoemail;

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
	protected $regexp = '/\\b[-a-z0-9_+.]+@[-a-z0-9.]*[a-z0-9]/Si';

	/**
	* @var string Name of the tag used to represent links
	*/
	protected $tagName = 'EMAIL';

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Add an attribute using the default email filter
		$filter = $this->configurator->attributeFilters->get('#email');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);

		// Set the default template
		$tag->template = '<a href="mailto:{@' . $this->attrName . '}"><xsl:apply-templates/></a>';
	}
}