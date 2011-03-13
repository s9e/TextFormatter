<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\PluginConfig;

class CensorConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used to mark censored words
	*/
	public $tagName = 'C';

	/**
	* @var string Default string used to replace censored words
	*/
	public $defaultReplacement = '****';

	/**
	* @var array
	*/
	protected $words = array();

	/**
	* @var array
	*/
	protected $replacements = array();

	public function setUp()
	{
		$this->cb->addTag(self::TAG_ID, array(
			'defaultRule' => 'deny'
		));

		$this->cb->addTagAttr($bbcodeId, 'with', 'text', array('isRequired' => false));

		$this->setTagTemplate(
			self::TAG_ID,
			'<xsl:choose><xsl:when test="@with"><xsl:value-of select="@with"/></xsl:when><xsl:otherwise>' . htmlspecialchars($defaultReplacement) . '</xsl:otherwise></xsl:choose>'
		);
	}

	public function addCensor($word, $replacement = null)
	{
		/**
		* 0 00 word
		* 1 01 word*
		* 2 10 *word
		* 3 11 *word*
		*/
		$k = (($word[0] === '*') << 1) + (substr($word, -1) === '*');

		/**
		* Remove leading and trailing asterisks
		*/
		$word = trim($word, '*');
		$this->words[$k][] = $word;

		if (isset($replacement))
		{
			$mask = (($k & 2) ? '#' : '#^')
			      . str_replace('\\*', '.*', preg_quote($word, '#'))
			      . (($k & 1) ? '#i' : '$#iD');

			if (preg_match('#[\\x80-\\xFF]#', $word))
			{
				/**
				* Non-ASCII characters get the Unicode treatment
				*/
				$mask .= 'u';
			}

			$this->replacements[$k][$mask] = $replacement;
		}
	}

	public function getConfig()
	{
		$config = array();

		foreach ($this->words as $k => $words)
		{
			$regexp = ConfigBuilder::buildRegexpFromList($words, array('*' => '\\pL*'));

			$config['regexp'][$k] = (($k & 2) ? '#\\pL*?' : '#\\b')
			                      . $regexp
			                      . (($k & 1) ? '\\pL*#i' : '\\b#i')
			                      . 'u';
		}

		if (!empty($this->replacements))
		{
			$config['replacements'] = $this->replacements;
		}

		return $config;
	}
}