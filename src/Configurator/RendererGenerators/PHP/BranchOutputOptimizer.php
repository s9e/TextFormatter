<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class BranchOutputOptimizer
{
	protected $cnt;
	protected $i;
	protected $tokens;
	public function optimize(array $tokens)
	{
		$this->tokens = $tokens;
		$this->i      = 0;
		$this->cnt    = \count($this->tokens);
		$php = '';
		while (++$this->i < $this->cnt)
			if ($this->tokens[$this->i][0] === \T_IF)
				$php .= $this->serializeIfBlock($this->parseIfBlock());
			else
				$php .= $this->serializeToken($this->tokens[$this->i]);
		unset($this->tokens);
		return $php;
	}
	protected function captureOutput()
	{
		$expressions = array();
		while ($this->skipOutputAssignment())
		{
			do
			{
				$expressions[] = $this->captureOutputExpression();
			}
			while ($this->tokens[$this->i++] === '.');
		}
		return $expressions;
	}
	protected function captureOutputExpression()
	{
		$parens = 0;
		$php = '';
		do
		{
			if ($this->tokens[$this->i] === ';')
				break;
			elseif ($this->tokens[$this->i] === '.' && !$parens)
				break;
			elseif ($this->tokens[$this->i] === '(')
				++$parens;
			elseif ($this->tokens[$this->i] === ')')
				--$parens;
			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while (++$this->i < $this->cnt);
		return $php;
	}
	protected function captureStructure()
	{
		$php = '';
		do
		{
			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while ($this->tokens[++$this->i] !== '{');
		++$this->i;
		return $php;
	}
	protected function isBranchToken()
	{
		return \in_array($this->tokens[$this->i][0], array(\T_ELSE, \T_ELSEIF, \T_IF), \true);
	}
	protected function mergeIfBranches(array $branches)
	{
		$lastBranch = \end($branches);
		if ($lastBranch['structure'] === 'else')
		{
			$before = $this->optimizeBranchesHead($branches);
			$after  = $this->optimizeBranchesTail($branches);
		}
		else
			$before = $after = array();
		$source = '';
		foreach ($branches as $branch)
			$source .= $this->serializeBranch($branch);
		return array(
			'before' => $before,
			'source' => $source,
			'after'  => $after
		);
	}
	protected function mergeOutput(array $left, array $right)
	{
		if (empty($left))
			return $right;
		if (empty($right))
			return $left;
		$k = \count($left) - 1;
		if (\substr($left[$k], -1) === "'" && $right[0][0] === "'")
		{
			$right[0] = \substr($left[$k], 0, -1) . \substr($right[0], 1);
			unset($left[$k]);
		}
		return \array_merge($left, $right);
	}
	protected function optimizeBranchesHead(array &$branches)
	{
		$before = $this->optimizeBranchesOutput($branches, 'head');
		foreach ($branches as &$branch)
		{
			if ($branch['body'] !== '' || !empty($branch['tail']))
				continue;
			$branch['tail'] = \array_reverse($branch['head']);
			$branch['head'] = array();
		}
		unset($branch);
		return $before;
	}
	protected function optimizeBranchesOutput(array &$branches, $which)
	{
		$expressions = array();
		while (isset($branches[0][$which][0]))
		{
			$expr = $branches[0][$which][0];
			foreach ($branches as $branch)
				if (!isset($branch[$which][0]) || $branch[$which][0] !== $expr)
					break 2;
			$expressions[] = $expr;
			foreach ($branches as &$branch)
				\array_shift($branch[$which]);
			unset($branch);
		}
		return $expressions;
	}
	protected function optimizeBranchesTail(array &$branches)
	{
		return $this->optimizeBranchesOutput($branches, 'tail');
	}
	protected function parseBranch()
	{
		$structure = $this->captureStructure();
		$head = $this->captureOutput();
		$body = '';
		$tail = array();
		$braces = 0;
		do
		{
			$tail = $this->mergeOutput($tail, \array_reverse($this->captureOutput()));
			if ($this->tokens[$this->i] === '}' && !$braces)
				break;
			$body .= $this->serializeOutput(\array_reverse($tail));
			$tail  = array();
			if ($this->tokens[$this->i][0] === \T_IF)
			{
				$child = $this->parseIfBlock();
				if ($body === '')
					$head = $this->mergeOutput($head, $child['before']);
				else
					$body .= $this->serializeOutput($child['before']);
				$body .= $child['source'];
				$tail  = $child['after'];
			}
			else
			{
				$body .= $this->serializeToken($this->tokens[$this->i]);
				if ($this->tokens[$this->i] === '{')
					++$braces;
				elseif ($this->tokens[$this->i] === '}')
					--$braces;
			}
		}
		while (++$this->i < $this->cnt);
		return array(
			'structure' => $structure,
			'head'      => $head,
			'body'      => $body,
			'tail'      => $tail
		);
	}
	protected function parseIfBlock()
	{
		$branches = array();
		do
		{
			$branches[] = $this->parseBranch();
		}
		while (++$this->i < $this->cnt && $this->isBranchToken());
		--$this->i;
		return $this->mergeIfBranches($branches);
	}
	protected function serializeBranch(array $branch)
	{
		if ($branch['structure'] === 'else'
		 && $branch['body']      === ''
		 && empty($branch['head'])
		 && empty($branch['tail']))
			return '';
		return $branch['structure'] . '{' . $this->serializeOutput($branch['head']) . $branch['body'] . $this->serializeOutput(\array_reverse($branch['tail'])) . '}';
	}
	protected function serializeIfBlock(array $block)
	{
		return $this->serializeOutput($block['before']) . $block['source'] . $this->serializeOutput(\array_reverse($block['after']));
	}
	protected function serializeOutput(array $expressions)
	{
		if (empty($expressions))
			return '';
		return '$this->out.=' . \implode('.', $expressions) . ';';
	}
	protected function serializeToken($token)
	{
		return (\is_array($token)) ? $token[1] : $token;
	}
	protected function skipOutputAssignment()
	{
		if ($this->tokens[$this->i    ][0] !== \T_VARIABLE
		 || $this->tokens[$this->i    ][1] !== '$this'
		 || $this->tokens[$this->i + 1][0] !== \T_OBJECT_OPERATOR
		 || $this->tokens[$this->i + 2][0] !== \T_STRING
		 || $this->tokens[$this->i + 2][1] !== 'out'
		 || $this->tokens[$this->i + 3][0] !== \T_CONCAT_EQUAL)
			 return \false;
		$this->i += 4;
		return \true;
	}
}