<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\PipeTables;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var integer Position of the cursor while parsing tables or adding tags
	*/
	protected $pos;

	/**
	* @var array Current table being parsed
	*/
	protected $table;

	/**
	* @var \s9e\TextFormatter\Parser\Tag
	*/
	protected $tableTag;

	/**
	* @var array[] List of tables
	*/
	protected $tables;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$this->text = $text;
		if ($this->config['overwriteMarkdown'])
		{
			$this->overwriteMarkdown();
		}
		if ($this->config['overwriteEscapes'])
		{
			$this->overwriteEscapes();
		}

		$this->captureTables();
		$this->processTables();

		unset($this->tables);
		unset($this->text);
	}

	/**
	* Add current line to a table
	*
	* @param  string $line Line of text
	* @return void
	*/
	protected function addLine($line)
	{
		$ignoreLen = 0;

		if (!isset($this->table))
		{
			$this->table = [];

			// Make the table start at the first non-space character
			preg_match('/^ */', $line, $m);
			$ignoreLen = strlen($m[0]);
			$line      = substr($line, $ignoreLen);
		}

		// Overwrite the outermost pipes
		$line = preg_replace('/^( *)\\|/', '$1 ', $line);
		$line = preg_replace('/\\|( *)$/', ' $1', $line);

		$this->table['rows'][] = ['line' => $line, 'pos' => $this->pos + $ignoreLen];
	}

	/**
	* Process current table's body
	*
	* @return void
	*/
	protected function addTableBody()
	{
		$i   = 1;
		$cnt = count($this->table['rows']);
		while (++$i < $cnt)
		{
			$this->addTableRow('TD', $this->table['rows'][$i]);
		}

		$this->createBodyTags($this->table['rows'][2]['pos'], $this->pos);
	}

	/**
	* Add a cell's tags for current table at current position
	*
	* @param  string $tagName Either TD or TH
	* @param  string $align   Either "left", "center", "right" or ""
	* @param  string $content Cell's text content
	* @return void
	*/
	protected function addTableCell($tagName, $align, $content)
	{
		$startPos  = $this->pos;
		$endPos    = $startPos + strlen($content);
		$this->pos = $endPos;

		preg_match('/^( *).*?( *)$/', $content, $m);
		if ($m[1])
		{
			$ignoreLen = strlen($m[1]);
			$this->createIgnoreTag($startPos, $ignoreLen);
			$startPos += $ignoreLen;
		}
		if ($m[2])
		{
			$ignoreLen = strlen($m[2]);
			$this->createIgnoreTag($endPos - $ignoreLen, $ignoreLen);
			$endPos -= $ignoreLen;
		}

		$this->createCellTags($tagName, $startPos, $endPos, $align);
	}

	/**
	* Process current table's head
	*
	* @return void
	*/
	protected function addTableHead()
	{
		$this->addTableRow('TH', $this->table['rows'][0]);
		$this->createHeadTags($this->table['rows'][0]['pos'], $this->pos);
	}

	/**
	* Process given table row
	*
	* @param  string $tagName Either TD or TH
	* @param  array  $row
	* @return void
	*/
	protected function addTableRow($tagName, $row)
	{
		$this->pos = $row['pos'];
		foreach (explode('|', $row['line']) as $i => $str)
		{
			if ($i > 0)
			{
				$this->createIgnoreTag($this->pos, 1);
				++$this->pos;
			}

			$align = (empty($this->table['cols'][$i])) ? '' : $this->table['cols'][$i];
			$this->addTableCell($tagName, $align, $str);
		}

		$this->createRowTags($row['pos'], $this->pos);
	}

	/**
	* Capture all pipe tables in current text
	*
	* @return void
	*/
	protected function captureTables()
	{
		unset($this->table);
		$this->tables = [];

		$this->pos = 0;
		foreach (explode("\n", $this->text) as $line)
		{
			if (strpos($line, '|') === false)
			{
				$this->endTable();
			}
			else
			{
				$this->addLine($line);
			}
			$this->pos += 1 + strlen($line);
		}
		$this->endTable();
	}

	/**
	* Create a pair of TBODY tags for given text span
	*
	* @param  integer $startPos
	* @param  integer $endPos
	* @return void
	*/
	protected function createBodyTags($startPos, $endPos)
	{
		$this->parser->addTagPair('TBODY', $startPos, 0, $endPos, 0, -103);
	}

	/**
	* Create a pair of TD or TH tags for given text span
	*
	* @param  string  $tagName  Either TD or TH
	* @param  integer $startPos
	* @param  integer $endPos
	* @param  string  $align    Either "left", "center", "right" or ""
	* @return void
	*/
	protected function createCellTags($tagName, $startPos, $endPos, $align)
	{
		if ($startPos === $endPos)
		{
			$tag = $this->parser->addSelfClosingTag($tagName, $startPos, 0, -101);
		}
		else
		{
			$tag = $this->parser->addTagPair($tagName, $startPos, 0, $endPos, 0, -101);
		}
		if ($align)
		{
			$tag->setAttribute('align', $align);
		}
	}

	/**
	* Create a pair of THEAD tags for given text span
	*
	* @param  integer $startPos
	* @param  integer $endPos
	* @return void
	*/
	protected function createHeadTags($startPos, $endPos)
	{
		$this->parser->addTagPair('THEAD', $startPos, 0, $endPos, 0, -103);
	}

	/**
	* Create an ignore tag for given text span
	*
	* @param  integer $pos
	* @param  integer $len
	* @return void
	*/
	protected function createIgnoreTag($pos, $len)
	{
		$this->tableTag->cascadeInvalidationTo($this->parser->addIgnoreTag($pos, $len, 1000));
	}

	/**
	* Create a pair of TR tags for given text span
	*
	* @param  integer $startPos
	* @param  integer $endPos
	* @return void
	*/
	protected function createRowTags($startPos, $endPos)
	{
		$this->parser->addTagPair('TR', $startPos, 0, $endPos, 0, -102);
	}

	/**
	* Create an ignore tag for given separator row
	*
	* @param  array $row
	* @return void
	*/
	protected function createSeparatorTag(array $row)
	{
		$this->createIgnoreTag($row['pos'] - 1, 1 + strlen($row['line']));
	}

	/**
	* Create a pair of TABLE tags for given text span
	*
	* @param  integer $startPos
	* @param  integer $endPos
	* @return void
	*/
	protected function createTableTags($startPos, $endPos)
	{
		$this->tableTag = $this->parser->addTagPair('TABLE', $startPos, 0, $endPos, 0, -104);
	}

	/**
	* End current buffered table
	*
	* @return void
	*/
	protected function endTable()
	{
		if ($this->hasValidTable())
		{
			$this->table['cols'] = $this->parseColumnAlignments($this->table['rows'][1]['line']);
			$this->tables[]      = $this->table;
		}
		unset($this->table);
	}

	/**
	* Test whether a valid table is currently buffered
	*
	* @return bool
	*/
	protected function hasValidTable()
	{
		return (isset($this->table) && count($this->table['rows']) > 2 && $this->isValidSeparator($this->table['rows'][1]['line']));
	}

	/**
	* Test whether given line is a valid separator
	*
	* @param  string $line
	* @return bool
	*/
	protected function isValidSeparator($line)
	{
		return (bool) preg_match('/^ *:?-+:?(?:(?:\\+| *\\| *):?-+:?)+ *$/', $line);
	}

	/**
	* Overwrite right angle brackets in given match
	*
	* @param  string[] $m
	* @return string
	*/
	protected function overwriteBlockquoteCallback(array $m)
	{
		return strtr($m[0], '!>', '  ');
	}

	/**
	* Overwrite escape sequences in current text
	*
	* @return void
	*/
	protected function overwriteEscapes()
	{
		if (strpos($this->text, '\\|') !== false)
		{
			$this->text = preg_replace('/\\\\[\\\\|]/', '..', $this->text);
		}
	}

	/**
	* Overwrite backticks in given match
	*
	* @param  string[] $m
	* @return string
	*/
	protected function overwriteInlineCodeCallback(array $m)
	{
		return strtr($m[0], '|', '.');
	}

	/**
	* Overwrite Markdown-style markup in current text
	*
	* @return void
	*/
	protected function overwriteMarkdown()
	{
		// Overwrite inline code spans
		if (strpos($this->text, '`') !== false)
		{
			$this->text = preg_replace_callback('/`[^`]*`/', [$this, 'overwriteInlineCodeCallback'], $this->text);
		}

		// Overwrite blockquotes
		if (strpos($this->text, '>') !== false)
		{
			$this->text = preg_replace_callback('/^(?:>!? ?)+/m', [$this, 'overwriteBlockquoteCallback'], $this->text);
		}
	}

	/**
	* Parse and return column alignments in given separator line
	*
	* @param  string   $line
	* @return string[]
	*/
	protected function parseColumnAlignments($line)
	{
		// Use a bitfield to represent the colons' presence and map it to the CSS value
		$align = [
			0b00 => '',
			0b01 => 'right',
			0b10 => 'left',
			0b11 => 'center'
		];

		$cols = [];
		preg_match_all('/(:?)-+(:?)/', $line, $matches, PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			$key = (!empty($m[1]) ? 2 : 0) + (!empty($m[2]) ? 1 : 0);
			$cols[] = $align[$key];
		}

		return $cols;
	}

	/**
	* Process current table declaration
	*
	* @return void
	*/
	protected function processCurrentTable()
	{
		$firstRow = $this->table['rows'][0];
		$lastRow  = end($this->table['rows']);
		$this->createTableTags($firstRow['pos'], $lastRow['pos'] + strlen($lastRow['line']));

		$this->addTableHead();
		$this->createSeparatorTag($this->table['rows'][1]);
		$this->addTableBody();
	}

	/**
	* Process all the captured tables
	*
	* @return void
	*/
	protected function processTables()
	{
		foreach ($this->tables as $table)
		{
			$this->table = $table;
			$this->processCurrentTable();
		}
	}
}