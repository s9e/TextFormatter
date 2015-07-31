<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
abstract class AbstractOptimizer
{
	protected $cnt;
	protected $i;
	protected $changed;
	protected $tokens;
	public function optimize($php)
	{
		$this->reset($php);
		$this->optimizeTokens();
		if ($this->changed)
			$php = $this->serialize();
		unset($this->tokens);
		return $php;
	}
	abstract protected function optimizeTokens();
	protected function reset($php)
	{
		$this->tokens  = \token_get_all('<?php ' . $php);
		$this->i       = 0;
		$this->cnt     = \count($this->tokens);
		$this->changed = \false;
	}
	protected function serialize()
	{
		unset($this->tokens[0]);
		$php = '';
		foreach ($this->tokens as $token)
			$php .= (\is_string($token)) ? $token : $token[1];
		return $php;
	}
	protected function skipToString($str)
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i] !== $str);
	}
	protected function skipWhitespace()
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i][0] === \T_WHITESPACE);
	}
	protected function unindentBlock($start, $end)
	{
		$this->i = $start;
		do
		{
			if ($this->tokens[$this->i][0] === \T_WHITESPACE || $this->tokens[$this->i][0] === \T_DOC_COMMENT)
				$this->tokens[$this->i][1] = \preg_replace("/^\t/m", '', $this->tokens[$this->i][1]);
		}
		while (++$this->i <= $end);
	}
}