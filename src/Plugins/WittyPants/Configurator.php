<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* This plugin combines some of the functionalities found in SmartyPants and Textile.
*
* @link http://daringfireball.net/projects/smartypants/
* @link http://textile.thresholdstate.com/
*/
class WittyPantsConfig extends ConfiguratorBase
{
	/**
	* @var string Name of attribute used to for the replacement
	*/
	protected $attrName = 'char';

	/**
	* @var string Name of the tag used to mark the text to replace
	*/
	protected $tagName = 'WP';

	/**
	* Plugin's setup
	*
	* Will initialize create the plugin's tag if it does not exist
	*/
	public function setUp()
	{
		if (!$this->configurator->tagExists($this->tagName))
		{
			$tag = $this->configurator->addTag($this->tagName);
			$tag->setAttribute($this->attrName);
			$tag->setTemplate('<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>');
		}
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