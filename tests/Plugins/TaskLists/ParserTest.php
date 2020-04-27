<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\TaskLists;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\TaskLists\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\TaskLists\Helper
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function setUp(): void
	{
		parent::setUp();
		$this->configurator->Litedown;
	}

	public function getParsingTests()
	{
		return self::fixTests([
			[
				[
					'- [x] checked',
					'- [X] Checked',
					'- [ ] unchecked'
				],
				[
					'<r><LIST><LI><s>- </s><TASK id="?" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="?" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="?" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				[
					'* [x] checked',
					'* [ ] unchecked'
				],
				[
					'<r><LIST><LI><s>* </s><TASK id="?" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>* </s><TASK id="?" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				[
					'- [x] checked',
					'- none',
					'- [ ] unchecked'
				],
				[
					'<r><LIST><LI><s>- </s><TASK id="?" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s>none</LI>',
					'<LI><s>- </s><TASK id="?" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				[
					'[list]',
					'[*][x] checked',
					'[*][X] Checked',
					'[*][ ] unchecked'
				],
				[
					'<r><LIST><s>[list]</s>',
					'<LI><s>[*]</s><TASK id="?" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>[*]</s><TASK id="?" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>[*]</s><TASK id="?" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('LIST');
					$configurator->BBCodes->add('*')->tagName = 'LI';
					$configurator->rulesGenerator->remove('ManageParagraphs');
				}
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				[
					'- [x] checked',
					'- [X] Checked',
					'- [ ] unchecked'
				],
				[
					'<ul><li data-task-id="?" data-task-state="checked"><input data-task-id="?" type="checkbox" checked disabled> checked</li>',
					'<li data-task-id="?" data-task-state="checked"><input data-task-id="?" type="checkbox" checked disabled> Checked</li>',
					'<li data-task-id="?" data-task-state="unchecked"><input data-task-id="?" type="checkbox" disabled> unchecked</li></ul>'
				]
			],
			[
				[
					'- [x] checked',
					'- [ ] unchecked'
				],
				[
					'<ul><li data-task-id="?" data-task-state="checked"><input data-task-id="?" type="checkbox" checked> checked</li>',
					'<li data-task-id="?" data-task-state="unchecked"><input data-task-id="?" type="checkbox"> unchecked</li></ul>'
				],
				[],
				function ($configurator)
				{
					$configurator->rendering->parameters['TASKLISTS_EDITABLE'] = '1';
				}
			],
		]);
	}

	protected static function fixTests($tests)
	{
		foreach ($tests as &$test)
		{
			if (is_array($test[0]))
			{
				$test[0] = implode("\n", $test[0]);
			}
			if (is_array($test[1]))
			{
				$test[1] = implode("\n", $test[1]);
			}

			$test += [null, null, [], null, null, null];
			$i = preg_match('(^<[rt][ />])', $test[1]) ? 5 : 4;
			$test[$i] = 'assertMatchesRegularExpression';

			$test[1] = '(^' . str_replace('\\?', '\\w++', preg_quote($test[1])) . '$)D';
		}

		return $tests;
	}
}