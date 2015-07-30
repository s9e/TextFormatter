<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class ControlStructuresOptimizer
{
	protected $braces;
	protected $cnt;
	protected $context;
	protected $i;
	protected $changed;
	protected $tokens;
	public function optimize($php)
	{
		$this->reset($php);
		$this->optimizeControlStructures();
		if ($this->changed)
			$php = $this->serialize();
		unset($this->tokens);
		return $php;
	}
	protected function blockEndsWithIf()
	{
		return \in_array($this->context['lastBlock'], [\T_IF, \T_ELSEIF], \true);
	}
	protected function isControlStructure()
	{
		return \in_array(
			$this->tokens[$this->i][0],
			[\T_ELSE, \T_ELSEIF, \T_FOR, \T_FOREACH, \T_IF, \T_WHILE],
			\true
		);
	}
	protected function isFollowedByElse()
	{
		if ($this->i > $this->cnt - 4)
			return \false;
		$k = $this->i + 1;
		if ($this->tokens[$k][0] === \T_WHITESPACE)
			++$k;
		return \in_array($this->tokens[$k][0], [\T_ELSEIF, \T_ELSE], \true);
	}
	protected function mustPreserveBraces()
	{
		return ($this->blockEndsWithIf() && $this->isFollowedByElse());
	}
	protected function optimizeControlStructures()
	{
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === ';')
				++$this->context['statements'];
			elseif ($this->tokens[$this->i] === '{')
				++$this->braces;
			elseif ($this->tokens[$this->i] === '}')
			{
				if ($this->context['braces'] === $this->braces)
					$this->processEndOfBlock();
				--$this->braces;
			}
			elseif ($this->isControlStructure())
				$this->processControlStructure();
	}
	protected function processControlStructure()
	{
		$savedIndex = $this->i;
		if (!\in_array($this->tokens[$this->i][0], [\T_ELSE, \T_ELSEIF], \true))
			++$this->context['statements'];
		if ($this->tokens[$this->i][0] !== \T_ELSE)
			$this->skipCondition();
		$this->skipWhitespace();
		if ($this->tokens[$this->i] !== '{')
		{
			$this->i = $savedIndex;
			return;
		}
		++$this->braces;
		$replacement = [\T_WHITESPACE, ''];
		if ($this->tokens[$savedIndex][0]  === \T_ELSE
		 && $this->tokens[$this->i + 1][0] !== \T_VARIABLE
		 && $this->tokens[$this->i + 1][0] !== \T_WHITESPACE)
			$replacement = [\T_WHITESPACE, ' '];
		$this->context['lastBlock'] = $this->tokens[$savedIndex][0];
		$this->context = [
			'braces'      => $this->braces,
			'index'       => $this->i,
			'lastBlock'   => \null,
			'parent'      => $this->context,
			'replacement' => $replacement,
			'savedIndex'  => $savedIndex,
			'statements'  => 0
		];
	}
	protected function processEndOfBlock()
	{
		if ($this->context['statements'] < 2 && !$this->mustPreserveBraces())
			$this->removeBracesInCurrentContext();
		$this->context = $this->context['parent'];
		$this->context['parent']['lastBlock'] = $this->context['lastBlock'];
	}
	protected function removeBracesInCurrentContext()
	{
		$this->tokens[$this->context['index']] = $this->context['replacement'];
		$this->tokens[$this->i] = ($this->context['statements']) ? [\T_WHITESPACE, ''] : ';';
		foreach ([$this->context['index'] - 1, $this->i - 1] as $tokenIndex)
			if ($this->tokens[$tokenIndex][0] === \T_WHITESPACE)
				$this->tokens[$tokenIndex][1] = '';
		if ($this->tokens[$this->context['savedIndex']][0] === \T_ELSE)
		{
			$j = 1 + $this->context['savedIndex'];
			while ($this->tokens[$j][0] === \T_WHITESPACE
			    || $this->tokens[$j][0] === \T_COMMENT
			    || $this->tokens[$j][0] === \T_DOC_COMMENT)
				++$j;
			if ($this->tokens[$j][0] === \T_IF)
			{
				$this->tokens[$j] = [\T_ELSEIF, 'elseif'];
				$j = $this->context['savedIndex'];
				$this->tokens[$j] = [\T_WHITESPACE, ''];
				if ($this->tokens[$j - 1][0] === \T_WHITESPACE)
					$this->tokens[$j - 1][1] = '';
				$this->unindentBlock($j, $this->i - 1);
				$this->tokens[$this->context['index']] = [\T_WHITESPACE, ''];
			}
		}
		$this->changed = \true;
	}
	protected function reset($php)
	{
		$this->tokens = \token_get_all('<?php ' . $php);
		$this->context = [
			'braces'      => 0,
			'index'       => -1,
			'parent'      => [],
			'preventElse' => \false,
			'savedIndex'  => 0,
			'statements'  => 0
		];
		$this->i       = 0;
		$this->cnt     = \count($this->tokens);
		$this->braces  = 0;
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
	protected function skipCondition()
	{
		$this->skipToString('(');
		$parens = 0;
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === ')')
				if ($parens)
					--$parens;
				else
					break;
			elseif ($this->tokens[$this->i] === '(')
				++$parens;
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