<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use DOMDocument,
    s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

class EscaperConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'ESC';

	public function setUp()
	{
		$this->cb->addTag($this->tagName, array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny',
			'template' => '<xsl:value-of select="substring(.,2)"/>'
		));
	}

	/**
	* @return array
	*/
	public function getConfig()
	{
		return array(
			'tagName' => $this->tagName,
			'regexp'  => '#\\\\.#us'
		);
	}
}