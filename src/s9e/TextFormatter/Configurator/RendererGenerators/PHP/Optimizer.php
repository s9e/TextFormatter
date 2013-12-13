<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

/**
* This class optimizes the code produced by the PHP rendere. It is not meant to be used on general
* purpose code
*/
class Optimizer
{
	/**
	* @var integer Maximum number iterations over the optimization passes
	*/
	public $maxLoops = 10;

	/**
	* Optimize the generated code
	*
	* @return string
	*/
	public function optimize($php)
	{
		$tokens = token_get_all('<?php ' . $php);
		$oldCnt = count($tokens);

		// Optimization passes, in order of execution
		$passes = [
			'optimizeOutConcatEqual',
			'optimizeConcatenations',
			'optimizeHtmlspecialchars'
		];

		// Limit the number of loops, in case something would make it loop indefinitely
		$remainingLoops = $this->maxLoops;
		do
		{
			$continue = false;

			foreach ($passes as $pass)
			{
				// Run the pass
				$this->$pass($tokens, $oldCnt);

				// If the array was modified, reset the keys and keep going
				$newCnt = count($tokens);
				if ($oldCnt !== $newCnt)
				{
					$tokens   = array_values($tokens);
					$oldCnt   = $newCnt;
					$continue = true;
				}
			}
		}
		while ($continue && --$remainingLoops);

		// Remove the first token, which should be T_OPEN_TAG, aka "<?php"
		unset($tokens[0]);

		// Rebuild the source
		$php = '';
		foreach ($tokens as $token)
		{
			$php .= (is_string($token)) ? $token : $token[1];
		}

		return $php;
	}

	/**
	* Optimize the control structures of a script
	*
	* Removes brackets in control structures wherever possible. Prevents the generation of EXT_STMT
	* opcodes where they're not strictly required.
	*
	* @return string $php
	* @return string
	*/
	public function optimizeControlStructures($php)
	{
		$tokens = token_get_all('<?php ' . $php);

		// Root context
		$context = [
			'braces'      => 0,
			'index'       => -1,
			'parent'      => [],
			'preventElse' => false,
			'statements'  => 0
		];

		$i       = 0;
		$cnt     = count($tokens);
		$braces  = 0;
		$rebuild = false;

		while (++$i < $cnt)
		{
			if ($tokens[$i][0] !== T_ELSE
			 && $tokens[$i][0] !== T_ELSEIF
			 && $tokens[$i][0] !== T_FOR
			 && $tokens[$i][0] !== T_FOREACH
			 && $tokens[$i][0] !== T_IF
			 && $tokens[$i][0] !== T_WHILE)
			{
				if ($tokens[$i] === ';')
				{
					++$context['statements'];
				}
				elseif ($tokens[$i] === '{')
				{
					++$braces;
				}
				elseif ($tokens[$i] === '}')
				{
					if ($context['braces'] === $braces)
					{
						// Test whether we should avoid removing the braces because it's followed by
						// an else/elseif that would become part of an inner if/elseif
						if ($context['preventElse'] && $i < $cnt + 3)
						{
							// Compute the index of the next non-whitespace token
							$j = $i + 1;

							if ($tokens[$j][0] === T_WHITESPACE)
							{
								++$j;
							}

							if ($tokens[$j][0] === T_ELSE
							 || $tokens[$j][0] === T_ELSEIF)
							{
								// Bump the number of statements to prevent the braces from being
								// removed
								$context['statements'] = 2;
							}
						}

						if ($context['statements'] < 2)
						{
							// Replace the first brace with the saved replacement
							$tokens[$context['index']] = $context['replacement'];

							// Remove the second brace or replace it with a semicolon if there are
							// no statements in this block
							$tokens[$i] = ($context['statements']) ? '' : ';';

							// Remove the whitespace before braces. This is mainly cosmetic
							foreach ([$context['index'] - 1, $i - 1] as $tokenIndex)
							{
								if (is_array($tokens[$tokenIndex])
								 && $tokens[$tokenIndex][0] === T_WHITESPACE)
								{
									unset($tokens[$tokenIndex]);
								}
							}

							$rebuild = true;
						}

						$context = $context['parent'];

						// Propagate the "preventElse" property upwards to handle multiple nested
						// if statements
						$context['parent']['preventElse'] = $context['preventElse'];
					}

					--$braces;
				}

				continue;
			}

			// Save the index so we can rewind back to it in case of failure
			$savedIndex = $i;

			// Count this control structure in this context's statements unless it's an elseif/else
			// in which case it's already been counted as part of the if
			if ($tokens[$i][0] !== T_ELSE && $tokens[$i][0] !== T_ELSEIF)
			{
				++$context['statements'];
			}

			if ($tokens[$i][0] !== T_ELSE)
			{
				// Move to the next (
				while (++$i < $cnt && $tokens[$i] !== '(');

				$parens = 0;
				while (++$i < $cnt)
				{
					if ($tokens[$i] === ')')
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
					elseif ($tokens[$i] === '(')
					{
						++$parens;
					}
				}
			}

			// Skip whitespace
			while (++$i < $cnt && $tokens[$i][0] === T_WHITESPACE);

			// Update context if we're inside of a new block
			if ($tokens[$i] === '{')
			{
				++$braces;

				// Replacement for the first brace
				$replacement = '';

				// Add a space after "else" if the brace is removed
				if ($tokens[$savedIndex    ][0] === T_ELSE
				 && $tokens[$savedIndex + 1][0] !== T_WHITESPACE)
				{
					$replacement = ' ';
				}

				// If the new block is an if or elseif block, prevent an else statement from parent
				// context to immediately follow it
				if ($tokens[$savedIndex][0] === T_IF
				 || $tokens[$savedIndex][0] === T_ELSEIF)
				{
					$context['preventElse'] = true;
				}
				else
				{
					$context['preventElse'] = false;
				}

				$context = [
					'braces'      => $braces,
					'index'       => $i,
					'parent'      => $context,
					'preventElse' => false,
					'replacement' => $replacement,
					'statements'  => 0
				];
			}
			else
			{
				// Rewind all the way to the original token
				$i = $savedIndex;
			}
		}

		// Remove the first token, which should be T_OPEN_TAG, aka "<?php"
		unset($tokens[0]);

		// Rebuild the source
		if ($rebuild)
		{
			$php = '';
			foreach ($tokens as $token)
			{
				$php .= (is_string($token)) ? $token : $token[1];
			}
		}

		return $php;
	}

	/**
	* Optimize T_CONCAT_EQUAL assignments in an array of PHP tokens
	*
	* Will only optimize $this->out.= assignments
	*
	* @param  array   &$tokens PHP tokens from tokens_get_all()
	* @param  integer  $cnt    Number of tokens
	* @return void
	*/
	protected function optimizeOutConcatEqual(array &$tokens, $cnt)
	{
		// Start at offset 4 to skip the first four tokens: <?php $this->out.=
		// We adjust the max value to account for the number of tokens ahead of the .= necessary to
		// apply this optimization, which is 8 (therefore the offset is one less)
		// 'foo';$this->out.='bar';
		$i   = 3;
		$max = $cnt - 9;

		while (++$i <= $max)
		{
			if ($tokens[$i][0] !== T_CONCAT_EQUAL)
			{
				continue;
			}

			// Test whether this T_CONCAT_EQUAL is preceded with $this->out
			if ($tokens[$i - 1][0] !== T_STRING
			 || $tokens[$i - 1][1] !== 'out'
			 || $tokens[$i - 2][0] !== T_OBJECT_OPERATOR
			 || $tokens[$i - 3][0] !== T_VARIABLE
			 || $tokens[$i - 3][1] !== '$this')
			{
				 continue;
			}

			do
			{
				// Move the cursor to next semicolon
				while (++$i < $cnt && $tokens[$i] !== ';');

				// Move the cursor past the semicolon
				if (++$i >= $cnt)
				{
					return;
				}

				// Test whether the assignment is followed by another $this->out.= assignment
				if ($tokens[$i    ][0] !== T_VARIABLE
				 || $tokens[$i    ][1] !== '$this'
				 || $tokens[$i + 1][0] !== T_OBJECT_OPERATOR
				 || $tokens[$i + 2][0] !== T_STRING
				 || $tokens[$i + 2][1] !== 'out'
				 || $tokens[$i + 3][0] !== T_CONCAT_EQUAL)
				{
					 break;
				}

				// Replace the semicolon between assignments with a concatenation operator
				$tokens[$i - 1] = '.';

				// Remove the following $this->out.= assignment and move the cursor past it
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 2]);
				unset($tokens[$i + 3]);
				$i += 3;
			}
			while ($i <= $max);
		}
	}

	/**
	* Optimize concatenations in an array of PHP tokens
	*
	* - Will precompute the result of the concatenation of constant strings
	* - Will replace the concatenation of two compatible htmlspecialchars() calls with one call to
	*   htmlspecialchars() on the concatenation of their first arguments
	*
	* @param  array   &$tokens PHP tokens from tokens_get_all()
	* @param  integer  $cnt    Number of tokens
	* @return void
	*/
	protected function optimizeConcatenations(array &$tokens, $cnt)
	{
		$i = 1;
		while (++$i < $cnt)
		{
			if ($tokens[$i] !== '.')
			{
				continue;
			}

			// Merge concatenated strings
			if ($tokens[$i - 1][0]    === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i + 1][0]    === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i - 1][1][0] === $tokens[$i + 1][1][0])
			{
				// Merge both strings into the right string
				$tokens[$i + 1][1] = substr($tokens[$i - 1][1], 0, -1)
				                   . substr($tokens[$i + 1][1], 1);

				// Unset the tokens that have been optimized away
				unset($tokens[$i - 1]);
				unset($tokens[$i]);

				// Advance the cursor
				++$i;

				continue;
			}

			// Merge htmlspecialchars() calls
			if ($tokens[$i + 1][0] === T_STRING
			 && $tokens[$i + 1][1] === 'htmlspecialchars'
			 && $tokens[$i + 2]    === '('
			 && $tokens[$i - 1]    === ')'
			 && $tokens[$i - 2][0] === T_LNUMBER
			 && $tokens[$i - 3]    === ',')
			{
				// Save the escape mode of the first call
				$escapeMode = $tokens[$i - 2][1];

				// Save the index of the comma that comes after the first argument of the first call
				$startIndex = $i - 3;

				// Save the index of the parenthesis that follows the second htmlspecialchars
				$endIndex = $i + 2;

				// Move the cursor to the first comma of the second call
				$i = $endIndex;
				$parens = 0;
				while (++$i < $cnt)
				{
					if ($tokens[$i] === ',' && !$parens)
					{
						break;
					}

					if ($tokens[$i] === '(')
					{
						++$parens;
					}
					elseif ($tokens[$i] === ')')
					{
						--$parens;
					}
				}

				if ($tokens[$i + 1][0] === T_LNUMBER
				 && $tokens[$i + 1][1] === $escapeMode)
				{
					// Replace the first comma of the first call with a concatenator operator
					$tokens[$startIndex] = '.';

					// Move the cursor back to the first comma then advance it and delete
					// everything up till the parenthesis of the second call, included
					$i = $startIndex;
					while (++$i <= $endIndex)
					{
						unset($tokens[$i]);
					}

					continue;
				}
			}
		}
	}

	/**
	* Optimize htmlspecialchars() calls
	*
	* - The result of htmlspecialchars() on literals is precomputed
	* - By default, the generator escapes all values, including variables that cannot contain
	*   special characters such as $node->localName. This pass removes those calls
	*
	* @param  array   &$tokens PHP tokens from tokens_get_all()
	* @param  integer  $cnt    Number of tokens
	* @return void
	*/
	protected function optimizeHtmlspecialchars(array &$tokens, $cnt)
	{
		$i   = 0;
		$max = $cnt - 7;

		while (++$i <= $max)
		{
			// Skip this token if it's not the first of the "htmlspecialchars(" sequence
			if ($tokens[$i    ][0] !== T_STRING
			 || $tokens[$i    ][1] !== 'htmlspecialchars'
			 || $tokens[$i + 1]    !== '(')
			{
				continue;
			}

			// Test whether a constant string is being escaped
			if ($tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i + 3]    === ','
			 && $tokens[$i + 4][0] === T_LNUMBER
			 && $tokens[$i + 5]    === ')')
			{
				// Escape the content of the T_CONSTANT_ENCAPSED_STRING token
				$tokens[$i + 2][1] = var_export(
					htmlspecialchars(
						stripslashes(substr($tokens[$i + 2][1], 1, -1)),
						$tokens[$i + 4][1]
					),
					true
				);

				// Remove the htmlspecialchars() call, except for the T_CONSTANT_ENCAPSED_STRING
				// token
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 3]);
				unset($tokens[$i + 4]);
				unset($tokens[$i + 5]);

				// Move the cursor past the call
				$i += 5;

				continue;
			}

			// Test whether a variable is being escaped
			if ($tokens[$i + 2][0] === T_VARIABLE
			 && $tokens[$i + 2][1]  === '$node'
			 && $tokens[$i + 3][0]  === T_OBJECT_OPERATOR
			 && $tokens[$i + 4][0]  === T_STRING
			 && ($tokens[$i + 4][1] === 'localName' || $tokens[$i + 4][1] === 'nodeName')
			 && $tokens[$i + 5]     === ','
			 && $tokens[$i + 6][0]  === T_LNUMBER
			 && $tokens[$i + 7]     === ')')
			{
				// Remove the htmlspecialchars() call, except for its first argument
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 5]);
				unset($tokens[$i + 6]);
				unset($tokens[$i + 7]);

				// Move the cursor past the call
				$i += 7;

				continue;
			}
		}
	}
}