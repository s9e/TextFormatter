<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\PluginConfig;

class ParagrapherConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'P';

	public function setUp()
	{
		if (!$this->cb->tagExists($this->tagName))
		{
			$this->cb->addTag(
				$this->tagName,
				array(
					'trimBefore'   => true,
					'ltrimContent' => true,
					'rtrimContent' => true,

					'rules' => array(
						'closeAscendant' => array($this->tagName)
					),

					'template' => '<p><xsl:apply-templates/></p>'
				)
			);
		}
	}

	public function getConfig()
	{
		return array(
			'regexp'  => '#(?:^|[\\r\\n]+)#',
			'tagName' => $this->tagName
		);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/ParagrapherParser.js');
	}
}