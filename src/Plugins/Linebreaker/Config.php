<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\ConfigBuilder;
use s9e\TextFormatter\Plugins\Config as PluginConfig;

class LinebreakerConfig extends PluginConfig
{
	public function setUp()
	{
		if (!$this->cb->tagExists('BR'))
		{
			$this->cb->addTag('BR')->setOptions(array(
				'defaultDescendantRule' => 'deny',
				'template' => '<br/>'
			));
		}
	}

	public function getConfig()
	{
		return array('regexp' => '#\\r?\\n#');
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/LinebreakerParser.js');
	}
}