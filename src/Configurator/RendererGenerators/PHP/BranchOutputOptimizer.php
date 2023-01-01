<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

class BranchOutputOptimizer
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
	* @var array Tokens from current source
	*/
	protected $tokens;

	/**
	* Optimize the code used to output content
	*
	* This method will go through the array of tokens, identify if/elseif/else blocks that contain
	* identical code at the beginning or the end and move the common code outside of the block
	*
	* @param  array $tokens Array of tokens from token_get_all()
	* @return string        Optimized code
	*/
	public function optimize(array $tokens)
	{
		$this->tokens = $tokens;
		$this->i      = 0;
		$this->cnt    = count($this->tokens);

		$php = '';
		while (++$this->i < $this->cnt)
		{
			if ($this->tokens[$this->i][0] === T_IF)
			{
				$php .= $this->serializeIfBlock($this->parseIfBlock());
			}
			else
			{
				$php .= $this->serializeToken($this->tokens[$this->i]);
			}
		}

		// Free the memory taken up by the tokens
		unset($this->tokens);

		return $php;
	}

	/**
	* Capture the expressions used in any number of consecutive output statements
	*
	* Starts looking at current index. Ends at the first token that's not part of an output
	* statement
	*
	* @return string[]
	*/
	protected function captureOutput()
	{
		$expressions = [];
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

	/**
	* Capture an expression used in output at current index
	*
	* Ends on "." or ";"
	*
	* @return string
	*/
	protected function captureOutputExpression()
	{
		$parens = 0;
		$php = '';
		do
		{
			if ($this->tokens[$this->i] === ';')
			{
				break;
			}
			elseif ($this->tokens[$this->i] === '.' && !$parens)
			{
				break;
			}
			elseif ($this->tokens[$this->i] === '(')
			{
				++$parens;
			}
			elseif ($this->tokens[$this->i] === ')')
			{
				--$parens;
			}

			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while (++$this->i < $this->cnt);

		return $php;
	}

	/**
	* Capture the source of a control structure from its keyword to its opening brace
	*
	* Ends after the brace, but the brace itself is not returned
	*
	* @return string
	*/
	protected function captureStructure()
	{
		$php = '';
		do
		{
			$php .= $this->serializeToken($this->tokens[$this->i]);
		}
		while ($this->tokens[++$this->i] !== '{');

		// Move past the {
		++$this->i;

		return $php;
	}

	/**
	* Test whether the token at current index is an if/elseif/else token
	*
	* @return bool
	*/
	protected function isBranchToken()
	{
		return in_array($this->tokens[$this->i][0], [T_ELSE, T_ELSEIF, T_IF], true);
	}

	/**
	* Merge the branches of an if/elseif/else block
	*
	* Returns an array that contains the following:
	*
	*  - before: array of PHP expressions to be output before the block
	*  - source: PHP code for the if block
	*  - after:  array of PHP expressions to be output after the block
	*
	* @param  array $branches
	* @return array
	*/
	protected function mergeIfBranches(array $branches)
	{
		// Test whether the branches cover all code paths. Without a "else" branch at the end, we
		// cannot optimize
		$lastBranch = end($branches);
		if ($lastBranch['structure'] === 'else')
		{
			$before = $this->optimizeBranchesHead($branches);
			$after  = $this->optimizeBranchesTail($branches);
		}
		else
		{
			$before = $after = [];
		}

		$source = '';
		foreach ($branches as $branch)
		{
			$source .= $this->serializeBranch($branch);
		}

		return [
			'before' => $before,
			'source' => $source,
			'after'  => $after
		];
	}

	/**
	* Merge two consecutive series of consecutive output expressions together
	*
	* @param  array $left  First series
	* @param  array $right Second series
	* @return array        Merged series
	*/
	protected function mergeOutput(array $left, array $right)
	{
		if (empty($left))
		{
			return $right;
		}

		if (empty($right))
		{
			return $left;
		}

		// Test whether we can merge the last expression on the left with the first expression on
		// the right
		$k = count($left) - 1;

		if (substr($left[$k], -1) === "'" && $right[0][0] === "'")
		{
			$right[0] = substr($left[$k], 0, -1) . substr($right[0], 1);
			unset($left[$k]);
		}

		return array_merge($left, $right);
	}

	/**
	* Optimize the "head" part of a series of branches in-place
	*
	* @param  array    &$branches Array of branches, modified in-place
	* @return string[]            PHP expressions removed from the "head" part of the branches
	*/
	protected function optimizeBranchesHead(array &$branches)
	{
		// Capture common output
		$before = $this->optimizeBranchesOutput($branches, 'head');

		// Move the branch output to the tail for branches that have no body
		foreach ($branches as &$branch)
		{
			if ($branch['body'] !== '' || !empty($branch['tail']))
			{
				continue;
			}

			$branch['tail'] = array_reverse($branch['head']);
			$branch['head'] = [];
		}
		unset($branch);

		return $before;
	}

	/**
	* Optimize the output of given branches
	*
	* @param  array    &$branches Array of branches
	* @param  string    $which    Which end to optimize ("head" or "tail")
	* @return string[]            PHP expressions removed from the given part of the branches
	*/
	protected function optimizeBranchesOutput(array &$branches, $which)
	{
		$expressions = [];
		while (isset($branches[0][$which][0]))
		{
			$expr = $branches[0][$which][0];
			foreach ($branches as $branch)
			{
				if (!isset($branch[$which][0]) || $branch[$which][0] !== $expr)
				{
					break 2;
				}
			}

			$expressions[] = $expr;
			foreach ($branches as &$branch)
			{
				array_shift($branch[$which]);
			}
			unset($branch);
		}

		return $expressions;
	}

	/**
	* Optimize the "tail" part of a series of branches in-place
	*
	* @param  array    &$branches Array of branches, modified in-place
	* @return string[]            PHP expressions removed from the "tail" part of the branches
	*/
	protected function optimizeBranchesTail(array &$branches)
	{
		return $this->optimizeBranchesOutput($branches, 'tail');
	}

	/**
	* Parse the if, elseif or else branch starting at current index
	*
	* Ends at the last }
	*
	* @return array Branch's data ("structure", "head", "body", "tail")
	*/
	protected function parseBranch()
	{
		// Record the control structure
		$structure = $this->captureStructure();

		// Record the output expressions at the start of this branch
		$head = $this->captureOutput();
		$body = '';
		$tail = [];

		$braces = 0;
		do
		{
			$tail = $this->mergeOutput($tail, array_reverse($this->captureOutput()));
			if ($this->tokens[$this->i] === '}' && !$braces)
			{
				break;
			}

			$body .= $this->serializeOutput(array_reverse($tail));
			$tail  = [];

			if ($this->tokens[$this->i][0] === T_IF)
			{
				$child = $this->parseIfBlock();

				// If this is the start of current branch, what's been optimized away and moved
				// outside, before the child branch is the head of this one. Otherwise it's just
				// part of its body
				if ($body === '')
				{
					$head = $this->mergeOutput($head, $child['before']);
				}
				else
				{
					$body .= $this->serializeOutput($child['before']);
				}

				$body .= $child['source'];
				$tail  = $child['after'];
			}
			else
			{
				$body .= $this->serializeToken($this->tokens[$this->i]);

				if ($this->tokens[$this->i] === '{')
				{
					++$braces;
				}
				elseif ($this->tokens[$this->i] === '}')
				{
					--$braces;
				}
			}
		}
		while (++$this->i < $this->cnt);

		return [
			'structure' => $structure,
			'head'      => $head,
			'body'      => $body,
			'tail'      => $tail
		];
	}

	/**
	* Parse the if block (including elseif/else branches) starting at current index
	*
	* @return array
	*/
	protected function parseIfBlock()
	{
		$branches = [];
		do
		{
			$branches[] = $this->parseBranch();
		}
		while (++$this->i < $this->cnt && $this->isBranchToken());

		// Move the index back to the last token used
		--$this->i;

		return $this->mergeIfBranches($branches);
	}

	/**
	* Serialize a recorded branch back to PHP
	*
	* @param  array  $branch
	* @return string
	*/
	protected function serializeBranch(array $branch)
	{
		// Optimize away "else{}" completely
		if ($branch['structure'] === 'else'
		 && $branch['body']      === ''
		 && empty($branch['head'])
		 && empty($branch['tail']))
		{
			return '';
		}

		return $branch['structure'] . '{' . $this->serializeOutput($branch['head']) . $branch['body'] . $this->serializeOutput(array_reverse($branch['tail'])) . '}';
	}

	/**
	* Serialize a series of recorded branch back to PHP
	*
	* @param  array  $block
	* @return string
	*/
	protected function serializeIfBlock(array $block)
	{
		return $this->serializeOutput($block['before']) . $block['source'] . $this->serializeOutput(array_reverse($block['after']));
	}

	/**
	* Serialize a series of output expressions
	*
	* @param  string[] $expressions Array of PHP expressions
	* @return string                PHP code used to append given expressions to the output
	*/
	protected function serializeOutput(array $expressions)
	{
		if (empty($expressions))
		{
			return '';
		}

		return '$this->out.=' . implode('.', $expressions) . ';';
	}

	/**
	* Serialize a token back to PHP
	*
	* @param  array|string $token Token from token_get_all()
	* @return string              PHP code
	*/
	protected function serializeToken($token)
	{
		return (is_array($token)) ? $token[1] : $token;
	}

	/**
	* Attempt to move past output assignment at current index
	*
	* @return bool Whether if an output assignment was skipped
	*/
	protected function skipOutputAssignment()
	{
		if ($this->tokens[$this->i    ][0] !== T_VARIABLE
		 || $this->tokens[$this->i    ][1] !== '$this'
		 || $this->tokens[$this->i + 1][0] !== T_OBJECT_OPERATOR
		 || $this->tokens[$this->i + 2][0] !== T_STRING
		 || $this->tokens[$this->i + 2][1] !== 'out'
		 || $this->tokens[$this->i + 3][0] !== T_CONCAT_EQUAL)
		{
			 return false;
		}

		// Move past the concat assignment
		$this->i += 4;

		return true;
	}
}