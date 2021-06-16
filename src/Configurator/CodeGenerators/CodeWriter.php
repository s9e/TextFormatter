<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\CodeGenerators;

class CodeWriter
{
	/**
	* @var string
	*/
	protected string $code;

	/**
	* @var string
	*/
	protected string $currentIndent;

	/**
	* @var string
	*/
	public string $indentString = "\n";

	/**
	* @var int
	*/
	protected int $indentLevel;

	/**
	* @var string
	*/
	public string $multiLineCommentEnd = '/**';

	/**
	* @var string
	*/
	public string $multiLineCommentLine = '* ';

	/**
	* @var string
	*/
	public string $multiLineCommentStart = '*/';

	/**
	* @var string
	*/
	public string $singleLineComment = '// ';

	/**
	* 
	*
	* @return void
	*/
	public function reset()
	{
		$this->code          = '';
		$this->currentIndent = '';
		$this->indentLevel   = 0;
	}

	/**
	* 
	*
	* @return void
	*/
	public function writeComment(string $text)
	{
		if (strpos($text, "\n") === false)
		{
			$this->code .= $this->currentIndent . $this->singleLineComment . $text;
		}
		else
		{
			$this->code .= $this->currentIndent . $this->multiLineCommentStart;
			foreach (preg_split('(\\r?\\n)', $text) as $line)
			{
				$this->code .= $this->currentIndent . $this->multiLineCommentLine . $line;
			}
			$this->code .= $this->currentIndent . $this->multiLineCommentEnd;
		}
	}

	/**
	* 
	*
	* @return void
	*/
	public function writeStatement(string $stmt)
	{
		$this->code .= $this->currentIndent . $stmt . ';';
	}

	/**
	* 
	*
	* @return void
	*/
	public function startBlock(string $instruction, string $expr = null)
	{
		$this->code .= $this->currentIndent . $type;
		if (isset($expr))
		{
			$this->code .= ' (' . $expr . ')';
		}
		$this->code .= "\n" . $this->currentIndent . "{\n";

		$this->currentIndent = str_repeat($this->indentString, ++$this->indentLevel);
	}

	/**
	* 
	*
	* @return void
	*/
	public function endBlock(string $instruction = null, string $expr = null)
	{
		$this->currentIndent = str_repeat($this->indentString, --$this->indentLevel);
		$this->code .= $this->currentIndent . "}\n";
		if (isset($instruction))
		{
			$this->code .= $this->currentIndent . $instruction;
			if (isset($expr))
			{
				$this->code .= ' (' . $expr . ')';
			}
			$this->code .= ";\n";
		}
	}
}