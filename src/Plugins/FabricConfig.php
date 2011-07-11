<?php

/**
* @package   s9e
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

/**
* The Fabric plugin is a partial implementation of the Textile format.
*
* @link http://textile.thresholdstate.com/
*/
class FabricConfig extends PluginConfig
{
	protected $tagsNeeded = array(
		// imagesAndLinks
		'URL',
		'IMG',

		// blockModifiers
		'DL',
		'DT',
		'DD',

		// phraseModifiers
		'_'  => 'EM',
		'__' => 'I',
		'*'  => 'STRONG',
		'**' => 'B',
		'??' => 'CITE',
		'-'  => 'DEL',
		'+'  => 'INS',
		'^'  => 'SUPER',
		'~'  => 'SUB',
		'@'  => 'CODE',
		'%'  => 'SPAN',
		'==' => 'NOPARSE',

		// acronyms
		'ACRONYM'
	);

	public function setUp()
	{
		foreach ($this->tagsNeeded as $tagName)
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

		$blockModifiers = array(
			'[\\#\\*]+ ',
			'::? ',
			';;? ',
			'h[1-6]\\. ',
			'p\\. ',
			'bq\\.(?: |:' . $urlRegexp . ')',
			'fn[1-9][0-9]{,2}\\. '
		);

		return array(
			'regexp' => array(
				'imagesAndLinks' =>
					'#([!"])(?P<text>.*?)(?P<attr>\\(.*?\\))?\\1(?P<url>:' . $urlRegexp . ')?#iS',

				'blockModifiers' => '#^(?:' . implode('|', $blockModifiers) . ')#Sm',

				'phraseModifiers' =>
					'#(?<!\\pL)(__|\\*\\*|\\?\\?|==|[_*\\-+^~@%]).+?(\\1)(?!\\pL)#Su',

				'acronyms' => '#([A-Z0-9]+)\\(([^\\)]+)\\)#S',

				'tableRow' => '#^\\s*\\|.*\\|$#ms'
			)
		);
	}
}