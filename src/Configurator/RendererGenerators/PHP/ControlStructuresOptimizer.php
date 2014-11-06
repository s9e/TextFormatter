<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
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

		if ($this->changed)
		{
			unset($this->tokens[0]);

			$php = '';
			foreach ($this->tokens as $token)
				$php .= (\is_string($token)) ? $token : $token[1];
		}

		unset($this->tokens);

		return $php;
	}

	protected function blockEndsWithIf()
	{
		return \in_array($this->context['lastBlock'], [304, 305], \true);
	}

	protected function isControlStructure()
	{
		return \in_array(
			$this->tokens[$this->i][0],
			[306, 305, 323, 325, 304, 321],
			\true
		);
	}

	protected function isFollowedByElse()
	{
		if ($this->i > $this->cnt - 4)
			return \false;

		$k = $this->i + 1;

		if ($this->tokens[$k][0] === 379)
			++$k;

		return \in_array($this->tokens[$k][0], [305, 306], \true);
	}

	protected function mustPreserveBraces()
	{
		return ($this->blockEndsWithIf() && $this->isFollowedByElse());
	}

	protected function processControlStructure()
	{
		$savedIndex = $this->i;

		if (!\in_array($this->tokens[$this->i][0], [306, 305], \true))
			++$this->context['statements'];

		if ($this->tokens[$this->i][0] !== 306)
			$this->skipCondition();

		$this->skipWhitespace();

		if ($this->tokens[$this->i] !== '{')
		{
			$this->i = $savedIndex;

			return;
		}

		++$this->braces;

		$replacement = [379, ''];

		if ($this->tokens[$savedIndex][0]  === 306
		 && $this->tokens[$this->i + 1][0] !== 312
		 && $this->tokens[$this->i + 1][0] !== 379)
			$replacement = [379, ' '];

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

		$this->tokens[$this->i] = ($this->context['statements']) ? [379, ''] : ';';

		foreach ([$this->context['index'] - 1, $this->i - 1] as $tokenIndex)
			if ($this->tokens[$tokenIndex][0] === 379)
				$this->tokens[$tokenIndex][1] = '';

		if ($this->tokens[$this->context['savedIndex']][0] === 306)
		{
			$j = 1 + $this->context['savedIndex'];

			while ($this->tokens[$j][0] === 379
			    || $this->tokens[$j][0] === 374
			    || $this->tokens[$j][0] === 375)
				++$j;

			if ($this->tokens[$j][0] === 304)
			{
				$this->tokens[$j] = [305, 'elseif'];

				$j = $this->context['savedIndex'];
				$this->tokens[$j] = [379, ''];

				if ($this->tokens[$j - 1][0] === 379)
					$this->tokens[$j - 1][1] = '';

				$this->unindentBlock($j, $this->i - 1);
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
		while (++$this->i < $this->cnt && $this->tokens[$this->i][0] === 379);
	}

	protected function unindentBlock($start, $end)
	{
		$this->i = $start;
		do
		{
			if ($this->tokens[$this->i][0] === 379 || $this->tokens[$this->i][0] === 375)
				$this->tokens[$this->i][1] = \preg_replace("/^\t/m", '', $this->tokens[$this->i][1]);
		}
		while (++$this->i <= $end);
	}
}