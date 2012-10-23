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
* The Fabric plugin is a partial implementation of the Textile format.
*
* @link http://textile.thresholdstate.com/
*/
class FabricConfig extends ConfiguratorBase
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
			if (!$this->configurator->tagExists($tagName))
			{
				$this->configurator->predefinedTags->{'add' . $tagName}();
			}
		}
	}

	public function getConfig()
	{
		$rm        = $this->configurator->getRegexpHelper();
		$urlRegexp = $rm->buildRegexpFromList($this->configurator->getAllowedSchemes()) . '://\\S+';

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