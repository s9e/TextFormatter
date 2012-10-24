<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	public function setUp()
	{
		if (!$this->configurator->tags->exists('URL'))
		{
			$this->configurator->predefinedTags->addURL();
		}
	}

	public function getConfig()
	{
		$schemeRegexp
			= RegexpBuilder::fromList($this->configurator->urlConfig->getAllowedSchemes());

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