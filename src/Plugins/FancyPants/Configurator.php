<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* This plugin combines some of the functionalities found in SmartyPants and Textile
*
* @link http://daringfireball.net/projects/smartypants/
* @link http://textile.thresholdstate.com/
*/
class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of attribute used to for the replacement
	*/
	protected $attrName = 'char';

	/**
	* @var string Name of the tag used to mark the text to replace
	*/
	protected $tagName = 'FP';

	/**
	* Plugin's setup
	*
	* Will initialize create the plugin's tag if it does not exist
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Create attribute
		$tag->attributes->add($this->attrName);

		// Create a template that replaces its content with the replacement chat
		$tag->template
			= '<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>';
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return array(
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		);
	}
}