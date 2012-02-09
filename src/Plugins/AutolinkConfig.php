<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

class AutolinkConfig extends PluginConfig
{
	public function setUp()
	{
		if (!$this->cb->tagExists('URL'))
		{
			$this->cb->predefinedTags->addURL();
		}
	}

	public function getConfig()
	{
		$schemeRegexp
			= $this->cb->getRegexpHelper()->buildRegexpFromList($this->cb->getAllowedSchemes());

		return array(
			'regexp' => '#' . $schemeRegexp . '://\\S(?:[^\\s\\[\\]]*(?:\\[\\w*\\])?)++#iS'
		);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/AutolinkParser.js');
	}
}