<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

class Helper
{
	public $allowed;

	public $attrName = 'with';

	public $defaultReplacement = '****';

	public $regexp = '/(?!)/';

	public $replacements = [];

	public $tagName = 'CENSOR';

	public function __construct(array $config)
	{
		foreach ($config as $k => $v)
			$this->$k = $v;
	}

	public function censorHtml($text)
	{
		$delim  = $this->regexp[0];
		$pos    = \strrpos($this->regexp, $delim);
		$regexp = \substr($this->regexp, 0, $pos)
		        . '(?=[^<">]*(?=<|$))'
		        . \substr($this->regexp, $pos);

		return \preg_replace_callback(
			$regexp,
			function ($m)
			{
				if (isset($this->allowed) && \preg_match($this->allowed, $m[0]))
					return $m[0];

				foreach ($this->replacements as list($regexp, $replacement))
					if (\preg_match($regexp, $m[0]))
						return \htmlspecialchars($replacement);

				return \htmlspecialchars($this->defaultReplacement);
			},
			$text
		);
	}

	public function censorText($text)
	{
		return \preg_replace_callback(
			$this->regexp,
			function ($m)
			{
				if (isset($this->allowed) && \preg_match($this->allowed, $m[0]))
					return $m[0];

				foreach ($this->replacements as list($regexp, $replacement))
					if (\preg_match($regexp, $m[0]))
						return $replacement;

				return $this->defaultReplacement;
			},
			$text
		);
	}

	public function reparse($xml)
	{
		if (\strpos($xml, '</' . $this->tagName . '>') !== \false)
		{
			$xml = \preg_replace_callback(
				'#<' . $this->tagName . '[^>]*>([^<]+)</' . $this->tagName . '>#',
				function ($m)
				{
					if (isset($this->allowed) && \preg_match($this->allowed, $m[0]))
						return $m[1];

					return (\preg_match($this->regexp, $m[1])) ? $this->buildTag($m[1]) : $m[1];
				},
				$xml
			);
		}

		$delim  = $this->regexp[0];
		$pos    = \strrpos($this->regexp, $delim);
		$regexp = \substr($this->regexp, 0, $pos)
		        . '(?=[^<">]*<(?!\\/(?-i)' . $this->tagName . '>))'
		        . \substr($this->regexp, $pos);

		$xml = \preg_replace_callback(
			$regexp,
			function ($m)
			{
				if (isset($this->allowed) && \preg_match($this->allowed, $m[0]))
					return $m[0];

				return $this->buildTag($m[0]);
			},
			$xml,
			-1,
			$cnt
		);

		if ($cnt && $xml[1] === 't')
		{
			$xml[1] = 'r';
			$xml[\strlen($xml) - 2] = 'r';
		}

		return $xml;
	}

	protected function buildTag($word)
	{
		$startTag = '<' . $this->tagName;

		foreach ($this->replacements as list($regexp, $replacement))
			if (\preg_match($regexp, $word))
			{
				$startTag .= ' ' . $this->attrName . '="' . \htmlspecialchars($replacement, \ENT_QUOTES) . '"';

				break;
			}

		return $startTag . '>' . $word . '</' . $this->tagName . '>';
	}
}