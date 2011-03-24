<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

/**
* The Fabric plugin is a partial implementation of the Textile format.
*
* @link http://textile.thresholdstate.com/
*/
class FabricConfig extends PluginConfig
{
	protected $phraseModifiers = array(
		'_'  => 'EM',
		'__' => 'I',
		'*'  => 'STRONG',
		'**' => 'B',
		'??' => 'QUOTE',
		'-'  => 'DEL',
		'+'  => 'INS',
		'^'  => 'SUPER',
		'~'  => 'SUB',
		'@'  => 'CODE',
		'%'  => 'SPAN',
		'==' => 'NOPARSE'
	);

	protected $tagsNeeded = array(
		'URL',
		'IMG'
	);

	public function setUp()
	{
		$tagsNeeded = array_merge($this->phraseModifiers, $this->tagsNeeded);

		foreach ($tagsNeeded as $modifier => $tagName)
		{
			if (!$this->cb->tagExists($tagName))
			{
				$this->cb->predefinedTags->{'add' . $tagName}();
			}
		}
	}

	public function getConfig()
	{
		$urlRegexp = ConfigBuilder::buildRegexpFromList($this->cb->getAllowedSchemes()) . '://\\S+';

		return array(
			'regexp' => array(
				'imagesAndLinks' =>
					'#([!"])(?P<text>.*?)(?P<attr>\\(.*?\\))?\\1(?P<url>:' . $urlRegexp . ')?#iS',

				'phraseModifiers' => '#(__|\\*\\*|\\?\\?|==|[_*\\-+^~@%])(?=.*?\\1)#S',
				'acronyms' => '#([A-Z0-9]+)\\(([^\\)]+)\\)#S',
				'tableRow' => '#^\\s*\\|.*\\|$#ms'
			)
		);
	}
}