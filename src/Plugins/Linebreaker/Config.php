<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Linebreaker;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\PluginConfig;

class Config extends PluginConfig
{
	/**
	* @var string Regexp that matches newlines
	*/
	protected $regexp = '#\\r?\\n#';

	/**
	* Plugin's setup
	*
	* Will create a <BR/> tag if one does not exist
	*
	* @return void
	*/
	public function setUp()
	{
		if (!isset($this->configurator->tags['BR']))
		{
			$this->configurator->tags->add('BR')->defaultTemplate = '<br/>';
		}
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/Parser.js');
	}
}