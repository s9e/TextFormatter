<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

abstract class AbstractOptimizer
{
	/**
	* @var integer Number of tokens
	*/
	protected $cnt;

	/**
	* @var integer Current token index
	*/
	protected $i;

	/**
	* @var boolean Whether the tokens have been changed
	*/
	protected $changed;

	/**
	* @var array Tokens from current source
	*/
	protected $tokens;

	/**
	* Optimize the control structures of a script
	*
	* Removes brackets in control structures wherever possible. Prevents the generation of EXT_STMT
	* opcodes where they're not strictly required.
	*
	* @param  string $php Original code
	* @return string      Optimized code
	*/
	public function optimize($php)
	{
		$this->reset($php);
		$this->optimizeTokens();

		// Rebuild the source if it has changed
		if ($this->changed)
		{
			$php = $this->serialize();
		}

		// Free the memory taken up by the tokens
		unset($this->tokens);

		return $php;
	}

	/**
	* Optimize the stored tokens
	*
	* @return void
	*/
	abstract protected function optimizeTokens();

	/**
	* Reset the internal state of this optimizer
	*
	* @param  string $php PHP source
	* @return void
	*/
	protected function reset($php)
	{
		$this->tokens  = token_get_all('<?php ' . $php);
		$this->i       = 0;
		$this->cnt     = count($this->tokens);
		$this->changed = false;
	}

	/**
	* Serialize the tokens back to source
	*
	* @return string
	*/
	protected function serialize()
	{
		// Remove the first token, which should be T_OPEN_TAG, aka "<?php"
		unset($this->tokens[0]);

		$php = '';
		foreach ($this->tokens as $token)
		{
			$php .= (is_string($token)) ? $token : $token[1];
		}

		return $php;
	}

	/**
	* Move the internal cursor until it reaches given string
	*
	* @param  string $str String to reach
	* @return void
	*/
	protected function skipToString($str)
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i] !== $str);
	}

	/**
	* Skip all whitespace
	*
	* @return void
	*/
	protected function skipWhitespace()
	{
		while (++$this->i < $this->cnt && $this->tokens[$this->i][0] === T_WHITESPACE);
	}

	/**
	* Remove one tab of indentation off a range of PHP tokens
	*
	* @param  integer $start  Index of the first token to unindent
	* @param  integer $end    Index of the last token to unindent
	* @return void
	*/
	protected function unindentBlock($start, $end)
	{
		$this->i = $start;
		do
		{
			if ($this->tokens[$this->i][0] === T_WHITESPACE || $this->tokens[$this->i][0] === T_DOC_COMMENT)
			{
				$this->tokens[$this->i][1] = preg_replace("/^\t/m", '', $this->tokens[$this->i][1]);
			}
		}
		while (++$this->i <= $end);
	}
}