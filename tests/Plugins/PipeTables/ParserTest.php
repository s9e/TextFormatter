<?php

namespace s9e\TextFormatter\Tests\Plugins\PipeTables;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\PipeTables\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\PipeTables\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	protected static function fixTests($tests)
	{
		foreach ($tests as &$test)
		{
			$test[0] = implode("\n", $test[0]);
			$test[1] = implode("\n", $test[1]);
		}

		return $tests;
	}

	public function getParsingTests()
	{
		return self::fixTests([
			[
				[
					'a|b',
					'-|-',
					'c|d'
				],
				[
					'<r><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></r>'
				]
			],
			[
				[
					'>|b',
					'-|-',
					'>|d'
				],
				[
					'<r><TABLE><THEAD><TR><TH>&gt;</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>&gt;</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></r>'
				]
			],
			[
				[
					'a|b',
					'-|-',
					'c|d',
					'',
					'x|y'
				],
				[
					'<r><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE>',
					'',
					'x|y</r>'
				]
			],
			[
				[
					'a|b',
					'-|-',
					'c|d',
					'',
					'a|b',
					'-|-',
					'c|d'
				],
				[
					'<r><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE>',
					'',
					'<TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></r>',
				]
			],
			[
				[
					'|a|b|',
					'|-|-|',
					'|c|d|'
				],
				[
					'<r><TABLE><THEAD><TR><i>|</i><TH>a</TH><i>|</i><TH>b</TH><i>|</i></TR></THEAD><i>',
					'|-|-|</i>',
					'<TBODY><TR><i>|</i><TD>c</TD><i>|</i><TD>d</TD><i>|</i></TR></TBODY></TABLE></r>'
				]
			],
			[
				// https://help.github.com/articles/organizing-information-with-tables/
				[
					' | a | b | ',
					' | - | - | ',
					' | c | d | '
				],
				[
					'<r> <TABLE><THEAD><TR><i>| </i><TH>a</TH><i> | </i><TH>b</TH><i> | </i></TR></THEAD><i>',
					' | - | - | </i>',
					'<TBODY><TR><i> | </i><TD>c</TD><i> | </i><TD>d</TD><i> | </i></TR></TBODY></TABLE></r>'
				]
			],
			[
				// https://help.github.com/articles/organizing-information-with-tables/#formatting-content-within-your-table
				[
					'| Left-aligned | Center-aligned | Right-aligned |',
					'| :---         |     :---:      |          ---: |',
					'| git status   | git status     | git status    |',
					'| git diff     | git diff       | git diff      |'
				],
				[
					'<r><TABLE><THEAD><TR><i>| </i><TH align="left">Left-aligned</TH><i> | </i><TH align="center">Center-aligned</TH><i> | </i><TH align="right">Right-aligned</TH><i> |</i></TR></THEAD><i>',
					'| :---         |     :---:      |          ---: |</i>',
					'<TBODY><TR><i>| </i><TD align="left">git status</TD><i>   | </i><TD align="center">git status</TD><i>     | </i><TD align="right">git status</TD><i>    |</i></TR>',
					'<TR><i>| </i><TD align="left">git diff</TD><i>     | </i><TD align="center">git diff</TD><i>       | </i><TD align="right">git diff</TD><i>      |</i></TR></TBODY></TABLE></r>'
				]
			],
			[
				[
					'a|b',
					'c|d'
				],
				[
					'<t>a|b',
					'c|d</t>'
				]
			],
			[
				[
					'a|b|c',
					':-|:-:|-:',
					'd|e|f'
				],
				[
					'<r><TABLE><THEAD><TR><TH align="left">a</TH><i>|</i><TH align="center">b</TH><i>|</i><TH align="right">c</TH></TR></THEAD><i>',
					':-|:-:|-:</i>',
					'<TBODY><TR><TD align="left">d</TD><i>|</i><TD align="center">e</TD><i>|</i><TD align="right">f</TD></TR></TBODY></TABLE></r>'
				]
			],
			[
				[
					'a|b',
					'-|-',
					'c\\|d'
				],
				[
					'<r><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c\</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></r>'
				]
			],
			[
				[
					'a|b',
					'-|-',
					'c\\||d'
				],
				[
					'<r><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'-|-</i>',
					'<TBODY><TR><TD>c<ESC><s>\</s>|</ESC></TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Escaper;
				}
			],
			[
				[
					'```',
					'a|b',
					'-|-',
					'c|d',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i>a|b',
					'-|-',
					'c|d<i>',
					'</i><e>```</e></CODE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
					$configurator->tags['CODE']->rules->ignoreTags();
				}
			],
			[
				[
					'    a|b',
					'    -|-',
					'    c|d'
				],
				[
					'<r><i>    </i><CODE>a|b',
					'<i>    </i>-|-',
					'<i>    </i>c|d</CODE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
					$configurator->tags['CODE']->rules->ignoreTags();
				}
			],
			[
				[
					'> a|b',
					'> -|-',
					'> c|d'
				],
				[
					'<t>&gt; a|b',
					'&gt; -|-',
					'&gt; c|d</t>'
				]
			],
			[
				[
					'> a|b',
					'> -|-',
					'> c|d'
				],
				[
					'<r><QUOTE><i>&gt; </i><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'&gt; -|-</i>',
					'<TBODY><TR><i>&gt; </i><TD>c</TD><i>|</i><TD>d</TD></TR></TBODY></TABLE></QUOTE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
			[
				[
					'> | a | b | ',
					'> | - | - | ',
					'> | c | d | '
				],
				[
					'<r><QUOTE><i>&gt; </i><TABLE><THEAD><TR><i>| </i><TH>a</TH><i> | </i><TH>b</TH><i> | </i></TR></THEAD><i>',
					'&gt; | - | - | </i>',
					'<TBODY><TR><i>&gt; | </i><TD>c</TD><i> | </i><TD>d</TD><i> | </i></TR></TBODY></TABLE></QUOTE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
			[
				[
					'> a|b',
					'> -|-',
					'> c|>'
				],
				[
					'<r><QUOTE><i>&gt; </i><TABLE><THEAD><TR><TH>a</TH><i>|</i><TH>b</TH></TR></THEAD><i>',
					'&gt; -|-</i>',
					'<TBODY><TR><i>&gt; </i><TD>c</TD><i>|</i><TD>&gt;</TD></TR></TBODY></TABLE></QUOTE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
			[
				// https://michelf.ca/projects/php-markdown/extra/#table
				[
					'| Function name | Description                    |',
					'| ------------- | ------------------------------ |',
					'| `help()`      | Display the help window.       |',
					'| `destroy()`   | **Destroy your computer!**     |'
				],
				[
					'<r><TABLE><THEAD><TR><i>| </i><TH>Function name</TH><i> | </i><TH>Description</TH><i>                    |</i></TR></THEAD><i>',
					'| ------------- | ------------------------------ |</i>',
					'<TBODY><TR><i>| </i><TD><C><s>`</s>help()<e>`</e></C></TD><i>      | </i><TD>Display the help window.</TD><i>       |</i></TR>',
					'<TR><i>| </i><TD><C><s>`</s>destroy()<e>`</e></C></TD><i>   | </i><TD><STRONG><s>**</s>Destroy your computer!<e>**</e></STRONG></TD><i>     |</i></TR></TBODY></TABLE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
			[
				// http://pandoc.org/MANUAL.html#extension-pipe_tables
				[
					'| One | Two   |',
					'|-----+-------|',
					'| my  | table |',
					'| is  | nice  |'
				],
				[
					'<r><TABLE><THEAD><TR><i>| </i><TH>One</TH><i> | </i><TH>Two</TH><i>   |</i></TR></THEAD><i>',
					'|-----+-------|</i>',
					'<TBODY><TR><i>| </i><TD>my</TD><i>  | </i><TD>table</TD><i> |</i></TR>',
					'<TR><i>| </i><TD>is</TD><i>  | </i><TD>nice</TD><i>  |</i></TR></TBODY></TABLE></r>'
				]
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				[
					'a|b',
					'-|-',
					'c|d'
				],
				[
					'<table><thead><tr><th>a</th><th>b</th></tr></thead>',
					'<tbody><tr><td>c</td><td>d</td></tr></tbody></table>'
				]
			],
			[
				[
					'a|b|c',
					':-|:-:|-:',
					'd|e|f'
				],
				[
					'<table><thead><tr><th style="text-align:left">a</th><th style="text-align:center">b</th><th style="text-align:right">c</th></tr></thead>',
					'<tbody><tr><td style="text-align:left">d</td><td style="text-align:center">e</td><td style="text-align:right">f</td></tr></tbody></table>'
				]
			],
			[
				[
					'> a|b',
					'> -|-',
					'> c|d'
				],
				[
					'<blockquote><table><thead><tr><th>a</th><th>b</th></tr></thead>',
					'<tbody><tr><td>c</td><td>d</td></tr></tbody></table></blockquote>'
				],
				[],
				function ($configurator)
				{
					$configurator->Litedown;
				}
			],
		]);
	}
}