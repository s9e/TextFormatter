<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

class CensorConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'C';

	/**
	* @var string Name of attribute used to for the replacement
	*/
	protected $attrName = 'with';

	/**
	* @var string Default string used to replace censored words
	*/
	protected $defaultReplacement = '****';

	/**
	* @var array  2D array of censored words/masks. First dimension is the type of masks
	*/
	protected $words = array();

	/**
	* @var array  Hash of replacements
	*/
	protected $replacements = array();

	public function setUp()
	{
		$this->cb->addTag(
			$this->tagName,
			array(
				'defaultRule' => 'deny',

				'attrs' => array(
					$this->attrName => array(
						'type'       => 'text',
						'isRequired' => false
					)
				),

				'template' =>
					'<xsl:choose>' .
						'<xsl:when test="@' . htmlspecialchars($this->attrName) . '">' .
							'<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>' .
						'</xsl:when>' .
						'<xsl:otherwise>' .
							htmlspecialchars($this->defaultReplacement) .
						'</xsl:otherwise>' .
					'</xsl:choose>'
			)
		);
	}

	/**
	* Add a word to the censor list
	*
	* @param string $word
	* @param string $replacement If left null, $this->defaultReplacement will be used
	*/
	public function addWord($word, $replacement = null)
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
			      . str_replace('\\*', '.*', str_replace('\\?', '\\pL', preg_quote($word, '#')))
			      . (($k & 1) ? '#i' : '$#iD');

			if (preg_match('#[\\?\\x80-\\xFF]#', $word))
			{
				/**
				* Non-ASCII characters and question mark jokers get the Unicode treatment
				*/
				$mask .= 'u';
			}

			$this->replacements[$k][$mask] = $replacement;
		}
	}

	public function getConfig()
	{
		if (empty($this->words))
		{
			return false;
		}

		$config = array(
			'tagName'  => $this->tagName,
			'attrName' => $this->attrName
		);

		foreach ($this->words as $k => $words)
		{
			$regexp = ConfigBuilder::buildRegexpFromList(
				$words,
				array('*' => '\\pL*', '?' => '\\pL')
			);

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