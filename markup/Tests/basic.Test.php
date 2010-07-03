<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testBasic extends \PHPUnit_Framework_TestCase
{
	public function testPlainText()
	{
		$parser = $this->getParser();

		$text     = 'This is some plain text.';
		$expected = '<pt>This is some plain text.</pt>';
		$actual   = $parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	protected function getParser()
	{
		$cb = new config_builder;
		$cb->addBBCode('b');
		$cb->addBBCode('i');
		$cb->addBBCode('u');
		$cb->addBBCode('url', array(
			'default_rule'     => 'deny',
			'default_param'    => 'url',
			'content_as_param' => true
		));

		$cb->addBBCodeRule('url', 'allow', 'b');
		$cb->addBBCodeRule('url', 'allow', 'i');
		$cb->addBBCodeRule('url', 'allow', 'u');

		$cb->addBBCodeParam('url', 'url', 'url', true);
		$cb->addBBCodeParam('url', 'title', 'text', false);

		$cb->addBBCodeAlias('url', 'link');

		$cb->addBBCode('code');

		$cb->addSmiley(':)');
		$cb->addSmiley(':[');

		$cb->addBBCode('list');
		$cb->addBBCode('li');
		$cb->addBBCodeAlias('li', '*');
		$cb->addBBCodeRule('li', 'require_parent', 'list');
		$cb->addBBCodeRule('li', 'close_parent', 'li');

		return new parser($cb->getConfig());
	}
}