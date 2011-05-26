<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

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
		return array(
			'regexp' => '#' . ConfigBuilder::buildRegexpFromList($this->cb->getAllowedSchemes()) . '://\\S(?:[^\\s\\[\\]]*(?:\\[\\w*\\])?)++#iS'
		);
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/AutolinkParser.js');
	}

	public function getJSConfig()
	{
		$config = $this->getConfig();

		// Javascript regexps don't support PCRE's possessive quantifier
		$config['regexp'] = str_replace('++', '+', $config['regexp']);

		return $config;
	}
}