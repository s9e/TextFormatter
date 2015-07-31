<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class Optimizer
{
	public $branchOutputOptimizer;
	protected $cnt;
	protected $i;
	public $maxLoops = 10;
	protected $tokens;
	public function __construct()
	{
		$this->branchOutputOptimizer = new BranchOutputOptimizer;
	}
	public function optimize($php)
	{
		$this->tokens = \token_get_all('<?php ' . $php);
		$this->cnt    = \count($this->tokens);
		$this->i      = 0;
		foreach ($this->tokens as &$token)
			if (\is_array($token))
				unset($token[2]);
		unset($token);
		$passes = array(
			'optimizeOutConcatEqual',
			'optimizeConcatenations',
			'optimizeHtmlspecialchars'
		);
		$remainingLoops = $this->maxLoops;
		do
		{
			$continue = \false;
			foreach ($passes as $pass)
			{
				$this->$pass();
				$cnt = \count($this->tokens);
				if ($this->cnt !== $cnt)
				{
					$this->tokens = \array_values($this->tokens);
					$this->cnt    = $cnt;
					$continue     = \true;
				}
			}
		}
		while ($continue && --$remainingLoops);
		$php = $this->branchOutputOptimizer->optimize($this->tokens);
		unset($this->tokens);
		return $php;
	}
	protected function isBetweenHtmlspecialcharCalls()
	{
		return ($this->tokens[$this->i + 1]    === array(\T_STRING, 'htmlspecialchars')
		     && $this->tokens[$this->i + 2]    === '('
		     && $this->tokens[$this->i - 1]    === ')'
		     && $this->tokens[$this->i - 2][0] === \T_LNUMBER
		     && $this->tokens[$this->i - 3]    === ',');
	}
	protected function isHtmlspecialcharSafeVar()
	{
		return ($this->tokens[$this->i    ]    === array(\T_VARIABLE,        '$node')
		     && $this->tokens[$this->i + 1]    === array(\T_OBJECT_OPERATOR, '->')
		     && ($this->tokens[$this->i + 2]   === array(\T_STRING,          'localName')
		      || $this->tokens[$this->i + 2]   === array(\T_STRING,          'nodeName'))
		     && $this->tokens[$this->i + 3]    === ','
		     && $this->tokens[$this->i + 4][0] === \T_LNUMBER
		     && $this->tokens[$this->i + 5]    === ')');
	}
	protected function isOutputAssignment()
	{
		return ($this->tokens[$this->i    ] === array(\T_VARIABLE,        '$this')
		     && $this->tokens[$this->i + 1] === array(\T_OBJECT_OPERATOR, '->')
		     && $this->tokens[$this->i + 2] === array(\T_STRING,          'out')
		     && $this->tokens[$this->i + 3] === array(\T_CONCAT_EQUAL,    '.='));
	}
	protected function isPrecededByOutputVar()
	{
		return ($this->tokens[$this->i - 1] === array(\T_STRING,          'out')
		     && $this->tokens[$this->i - 2] === array(\T_OBJECT_OPERATOR, '->')
		     && $this->tokens[$this->i - 3] === array(\T_VARIABLE,        '$this'));
	}
	protected function mergeConcatenatedHtmlSpecialChars()
	{
		if (!$this->isBetweenHtmlspecialcharCalls())
			 return \false;
		$escapeMode = $this->tokens[$this->i - 2][1];
		$startIndex = $this->i - 3;
		$endIndex = $this->i + 2;
		$this->i = $endIndex;
		$parens = 0;
		while (++$this->i < $this->cnt)
		{
			if ($this->tokens[$this->i] === ',' && !$parens)
				break;
			if ($this->tokens[$this->i] === '(')
				++$parens;
			elseif ($this->tokens[$this->i] === ')')
				--$parens;
		}
		if ($this->tokens[$this->i + 1] !== array(\T_LNUMBER, $escapeMode))
			return \false;
		$this->tokens[$startIndex] = '.';
		$this->i = $startIndex;
		while (++$this->i <= $endIndex)
			unset($this->tokens[$this->i]);
		return \true;
	}
	protected function mergeConcatenatedStrings()
	{
		if ($this->tokens[$this->i - 1][0]    !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i + 1][0]    !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i - 1][1][0] !== $this->tokens[$this->i + 1][1][0])
			return \false;
		$this->tokens[$this->i + 1][1] = \substr($this->tokens[$this->i - 1][1], 0, -1)
		                               . \substr($this->tokens[$this->i + 1][1], 1);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[$this->i]);
		++$this->i;
		return \true;
	}
	protected function optimizeOutConcatEqual()
	{
		$this->i = 3;
		while ($this->skipTo(array(\T_CONCAT_EQUAL, '.=')))
		{
			if (!$this->isPrecededByOutputVar())
				 continue;
			while ($this->skipPast(';'))
			{
				if (!$this->isOutputAssignment())
					 break;
				$this->tokens[$this->i - 1] = '.';
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
				unset($this->tokens[$this->i++]);
			}
		}
	}
	protected function optimizeConcatenations()
	{
		$this->i = 1;
		while ($this->skipTo('.'))
			$this->mergeConcatenatedStrings() || $this->mergeConcatenatedHtmlSpecialChars();
	}
	protected function optimizeHtmlspecialchars()
	{
		$this->i = 0;
		while ($this->skipPast(array(\T_STRING, 'htmlspecialchars')))
			if ($this->tokens[$this->i] === '(')
			{
				++$this->i;
				$this->replaceHtmlspecialcharsLiteral() || $this->removeHtmlspecialcharsSafeVar();
			}
	}
	protected function removeHtmlspecialcharsSafeVar()
	{
		if (!$this->isHtmlspecialcharSafeVar())
			 return \false;
		unset($this->tokens[$this->i - 2]);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[$this->i + 3]);
		unset($this->tokens[$this->i + 4]);
		unset($this->tokens[$this->i + 5]);
		$this->i += 6;
		return \true;
	}
	protected function replaceHtmlspecialcharsLiteral()
	{
		if ($this->tokens[$this->i    ][0] !== \T_CONSTANT_ENCAPSED_STRING
		 || $this->tokens[$this->i + 1]    !== ','
		 || $this->tokens[$this->i + 2][0] !== \T_LNUMBER
		 || $this->tokens[$this->i + 3]    !== ')')
			return \false;
		$this->tokens[$this->i][1] = \var_export(
			\htmlspecialchars(
				\stripslashes(\substr($this->tokens[$this->i][1], 1, -1)),
				$this->tokens[$this->i + 2][1]
			),
			\true
		);
		unset($this->tokens[$this->i - 2]);
		unset($this->tokens[$this->i - 1]);
		unset($this->tokens[++$this->i]);
		unset($this->tokens[++$this->i]);
		unset($this->tokens[++$this->i]);
		return \true;
	}
	protected function skipPast($token)
	{
		return ($this->skipTo($token) && ++$this->i < $this->cnt);
	}
	protected function skipTo($token)
	{
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i] === $token)
				return \true;
		return \false;
	}
}