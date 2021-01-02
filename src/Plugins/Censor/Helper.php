<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

class Helper
{
	/**
	* @var string Regexp matching whitelisted words
	*/
	public $allowed;

	/**
	* @var string Name of attribute used for the replacement
	*/
	public $attrName = 'with';

	/**
	* @var string Default string used to replace censored words
	*/
	public $defaultReplacement = '****';

	/**
	* @var string Regexp matching blacklisted words in plain text
	*/
	public $regexp = '/(?!)/';

	/**
	* @var string Regexp matching blacklisted words in HTML
	*/
	public $regexpHtml = '/(?!)/';

	/**
	* @var array Array of [regexp => replacement]
	*/
	public $replacements = [];

	/**
	* @var string Name of the tag used to mark censored words
	*/
	public $tagName = 'CENSOR';

	/**
	* Constructor
	*
	* @param  array $config Helper's config
	*/
	public function __construct(array $config)
	{
		foreach ($config as $k => $v)
		{
			$this->$k = $v;
		}
	}

	/**
	* Censor text nodes inside of HTML code
	*
	* NOTE: will only recognize attributes that are enclosed in double quotes
	*
	* @param  string $html             Original HTML
	* @param  bool   $censorAttributes Whether to censor the content of attributes
	* @return string                   Censored HTML
	*/
	public function censorHtml($html, $censorAttributes = false)
	{
		$attributesExpr = '';
		if ($censorAttributes)
		{
			$attributesExpr = '|[^<">]*+(?="(?> [-\\w]+="[^"]*+")*+\\/?>)';
		}

		// Modify the original regexp so that it only matches text nodes and optionally attribute
		// values
		$delim  = $this->regexpHtml[0];
		$pos    = strrpos($this->regexpHtml, $delim);
		$regexp = $delim
		        . '(?<!&|&#)'
		        . substr($this->regexpHtml, 1, $pos - 1)
		        . '(?=[^<>]*+(?=<|$)' . $attributesExpr . ')'
		        . substr($this->regexpHtml, $pos);

		return preg_replace_callback(
			$regexp,
			function ($m)
			{
				return htmlspecialchars($this->getReplacement(html_entity_decode($m[0], ENT_QUOTES, 'UTF-8')), ENT_QUOTES);
			},
			$html
		);
	}

	/**
	* Censor given plain text
	*
	* @param  string $text Original text
	* @return string       Censored text
	*/
	public function censorText($text)
	{
		return preg_replace_callback(
			$this->regexp,
			function ($m)
			{
				return $this->getReplacement($m[0]);
			},
			$text
		);
	}

	/**
	* Test whether given word is censored
	*
	* @param  string $word
	* @return bool
	*/
	public function isCensored($word)
	{
		return (preg_match($this->regexp, $word) && !$this->isAllowed($word));
	}

	/**
	* Get the replacement for given word
	*
	* @param  string $word Original word
	* @return string       Replacement if the word is censored, or the original word otherwise
	*/
	protected function getReplacement($word)
	{
		if ($this->isAllowed($word))
		{
			return $word;
		}

		foreach ($this->replacements as list($regexp, $replacement))
		{
			if (preg_match($regexp, $word))
			{
				return $replacement;
			}
		}

		return $this->defaultReplacement;
	}

	/**
	* Test whether given word is allowed (whitelisted)
	*
	* @param  string $word
	* @return bool
	*/
	protected function isAllowed($word)
	{
		return (isset($this->allowed) && preg_match($this->allowed, $word));
	}
}