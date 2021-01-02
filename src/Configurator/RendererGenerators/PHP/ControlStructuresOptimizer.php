<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

/**
* Optimize the control structures of a script
*
* Removes brackets in control structures wherever possible. Prevents the generation of EXT_STMT
* opcodes where they're not strictly required.
*/
class ControlStructuresOptimizer extends AbstractOptimizer
{
	/**
	* @var integer Number of braces encountered in current source
	*/
	protected $braces;

	/**
	* @var array Current context
	*/
	protected $context;

	/**
	* Test whether current block ends with an if or elseif control structure
	*
	* @return bool
	*/
	protected function blockEndsWithIf()
	{
		return in_array($this->context['lastBlock'], [T_IF, T_ELSEIF], true);
	}

	/**
	* Test whether the token at current index is a control structure
	*
	* @return bool
	*/
	protected function isControlStructure()
	{
		return in_array(
			$this->tokens[$this->i][0],
			[T_ELSE, T_ELSEIF, T_FOR, T_FOREACH, T_IF, T_WHILE],
			true
		);
	}

	/**
	* Test whether current block is followed by an elseif/else structure
	*
	* @return bool
	*/
	protected function isFollowedByElse()
	{
		if ($this->i > $this->cnt - 4)
		{
			// It doesn't have room for another block
			return false;
		}

		// Compute the index of the next non-whitespace token
		$k = $this->i + 1;

		if ($this->tokens[$k][0] === T_WHITESPACE)
		{
			++$k;
		}

		return in_array($this->tokens[$k][0], [T_ELSEIF, T_ELSE], true);
	}

	/**
	* Test whether braces must be preserved in current context
	*
	* @return bool
	*/
	protected function mustPreserveBraces()
	{
		// If current block ends with if/elseif and is followed by elseif/else, we must preserve
		// its braces to prevent it from merging with the outer elseif/else. IOW, we must preserve
		// the braces if "if{if{}}else" would become "if{if else}"
		return ($this->blockEndsWithIf() && $this->isFollowedByElse());
	}

	/**
	* Optimize control structures in stored tokens
	*
	* @return void
	*/
	protected function optimizeTokens()
	{
		while (++$this->i < $this->cnt)
		{
			if ($this->tokens[$this->i] === ';')
			{
				++$this->context['statements'];
			}
			elseif ($this->tokens[$this->i] === '{')
			{
				++$this->braces;
			}
			elseif ($this->tokens[$this->i] === '}')
			{
				if ($this->context['braces'] === $this->braces)
				{
					$this->processEndOfBlock();
				}

				--$this->braces;
			}
			elseif ($this->isControlStructure())
			{
				$this->processControlStructure();
			}
		}
	}

	/**
	* Process the control structure starting at current index
	*
	* @return void
	*/
	protected function processControlStructure()
	{
		// Save the index so we can rewind back to it in case of failure
		$savedIndex = $this->i;

		// Count this control structure in this context's statements unless it's an elseif/else
		// in which case it's already been counted as part of the if
		if (!in_array($this->tokens[$this->i][0], [T_ELSE, T_ELSEIF], true))
		{
			++$this->context['statements'];
		}

		// If the control structure is anything but an "else", skip its condition to reach the first
		// brace or statement
		if ($this->tokens[$this->i][0] !== T_ELSE)
		{
			$this->skipCondition();
		}

		$this->skipWhitespace();

		// Abort if this control structure does not use braces
		if ($this->tokens[$this->i] !== '{')
		{
			// Rewind all the way to the original token
			$this->i = $savedIndex;

			return;
		}

		++$this->braces;

		// Replacement for the first brace
		$replacement = [T_WHITESPACE, ''];

		// Add a space after "else" if the brace is removed and it's not followed by whitespace or a
		// variable
		if ($this->tokens[$savedIndex][0]  === T_ELSE
		 && $this->tokens[$this->i + 1][0] !== T_VARIABLE
		 && $this->tokens[$this->i + 1][0] !== T_WHITESPACE)
		{
			$replacement = [T_WHITESPACE, ' '];
		}

		// Record the token of the control structure (T_IF, T_WHILE, etc...) in the current context
		$this->context['lastBlock'] = $this->tokens[$savedIndex][0];

		// Create a new context
		$this->context = [
			'braces'      => $this->braces,
			'index'       => $this->i,
			'lastBlock'   => null,
			'parent'      => $this->context,
			'replacement' => $replacement,
			'savedIndex'  => $savedIndex,
			'statements'  => 0
		];
	}

	/**
	* Process the block ending at current index
	*
	* @return void
	*/
	protected function processEndOfBlock()
	{
		if ($this->context['statements'] < 2 && !$this->mustPreserveBraces())
		{
			$this->removeBracesInCurrentContext();
		}

		$this->context = $this->context['parent'];

		// Propagate the "lastBlock" property upwards to handle multiple nested if statements
		$this->context['parent']['lastBlock'] = $this->context['lastBlock'];
	}

	/**
	* Remove the braces surrounding current context
	*
	* @return void
	*/
	protected function removeBracesInCurrentContext()
	{
		// Replace the first brace with the saved replacement
		$this->tokens[$this->context['index']] = $this->context['replacement'];

		// Remove the second brace or replace it with a semicolon if there are no statements in this
		// block
		$this->tokens[$this->i] = ($this->context['statements']) ? [T_WHITESPACE, ''] : ';';

		// Remove the whitespace before braces. This is mainly cosmetic
		foreach ([$this->context['index'] - 1, $this->i - 1] as $tokenIndex)
		{
			if ($this->tokens[$tokenIndex][0] === T_WHITESPACE)
			{
				$this->tokens[$tokenIndex][1] = '';
			}
		}

		// Test whether the current block followed an else statement then test whether this
		// else was followed by an if
		if ($this->tokens[$this->context['savedIndex']][0] === T_ELSE)
		{
			$j = 1 + $this->context['savedIndex'];

			while ($this->tokens[$j][0] === T_WHITESPACE
			    || $this->tokens[$j][0] === T_COMMENT
			    || $this->tokens[$j][0] === T_DOC_COMMENT)
			{
				++$j;
			}

			if ($this->tokens[$j][0] === T_IF)
			{
				// Replace if with elseif
				$this->tokens[$j] = [T_ELSEIF, 'elseif'];

				// Remove the original else
				$j = $this->context['savedIndex'];
				$this->tokens[$j] = [T_WHITESPACE, ''];

				// Remove any whitespace before the original else
				if ($this->tokens[$j - 1][0] === T_WHITESPACE)
				{
					$this->tokens[$j - 1][1] = '';
				}

				// Unindent what was the else's content
				$this->unindentBlock($j, $this->i - 1);

				// Ensure that the brace after the now-removed "else" was not replaced with a space
				$this->tokens[$this->context['index']] = [T_WHITESPACE, ''];
			}
		}

		$this->changed = true;
	}

	/**
	* {@inheritdoc}
	*/
	protected function reset($php)
	{
		parent::reset($php);

		$this->braces  = 0;
		$this->context = [
			'braces'      => 0,
			'index'       => -1,
			'parent'      => [],
			'preventElse' => false,
			'savedIndex'  => 0,
			'statements'  => 0
		];
	}

	/**
	* Skip the condition of a control structure
	*
	* @return void
	*/
	protected function skipCondition()
	{
		// Reach the opening parenthesis
		$this->skipToString('(');

		// Iterate through tokens until we have a match for every left parenthesis
		$parens = 0;
		while (++$this->i < $this->cnt)
		{
			if ($this->tokens[$this->i] === ')')
			{
				if ($parens)
				{
					--$parens;
				}
				else
				{
					break;
				}
			}
			elseif ($this->tokens[$this->i] === '(')
			{
				++$parens;
			}
		}
	}
}