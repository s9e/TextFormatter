<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Blocks;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Emphasis;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\ForcedLineBreaks;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Images;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\InlineCode;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\LinkReferences;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Links;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Strikethrough;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Subscript;
use s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Superscript;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		$text = new ParsedText($text);
		$text->decodeHtmlEntities = $this->config['decodeHtmlEntities'];
		$pass=new Blocks($this->parser, $text);$pass->parse();
		$pass=new LinkReferences($this->parser, $text);$pass->parse();
		$pass=new InlineCode($this->parser, $text);$pass->parse();
		$pass=new Images($this->parser, $text);$pass->parse();
		$pass=new Links($this->parser, $text);$pass->parse();
		$pass=new Strikethrough($this->parser, $text);$pass->parse();
		$pass=new Subscript($this->parser, $text);$pass->parse();
		$pass=new Superscript($this->parser, $text);$pass->parse();
		$pass=new Emphasis($this->parser, $text);$pass->parse();
		$pass=new ForcedLineBreaks($this->parser, $text);$pass->parse();
	}
}