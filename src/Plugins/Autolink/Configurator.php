<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Validators\AttributeName;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute that stores the link's URL
	*/
	protected $attrName = 'url';

	/**
	* @var string Name of the tag used to represent links
	*/
	protected $tagName = 'URL';

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	public function setUp()
	{
		// If the tag does not exist...
		if (!isset($this->configurator->tags[$this->tagName]))
		{
			// Create a tag
			$tag = $this->configurator->tags->add($this->tagName);

			// Add an attribute using the #url filter
			$tag->attributes->add($this->attrName)->filterChain->append('#url');

			// Set the default template
			$tag->defaultTemplate
				= '<a href="{@' . $this->attrName . '}"><xsl:apply-templates/></a>';
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function toConfig()
	{
		$schemeRegexp
			= RegexpBuilder::fromList($this->configurator->urlConfig->getAllowedSchemes());

		return array(
			'regexp' => '#' . $schemeRegexp . '://\\S(?:[^\\s\\[\\]]*(?:\\[\\w*\\])?)++#iS'
		);
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