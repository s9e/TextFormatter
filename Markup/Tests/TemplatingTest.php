<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class TemplatingTest extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXMLThrowsAnException()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');
		$cb->setBBCodeTemplate('b', '<b><a></b>');
	}

	/**
	* @dataProvider BBCodeExamples
	*/
	public function testAddBBCodeFromExample($def, $tpl, $flags, $src, $expected, $msg = null)
	{
		$cb = new ConfigBuilder;

		try
		{
			$cb->addBBCodeFromExample($def, $tpl, $flags);
			$actual = $cb->getRenderer()->render($cb->getParser()->parse($src));

			if (isset($expected))
			{
				$this->assertSame($expected, $actual);
			}
			else
			{
				$this->fail('Should have failed with an exception containing "' . $msg . '"');
			}
		}
		catch (\Exception $e)
		{
			if (strpos(get_class($e), 'PHPUnit') !== false)
			{
				throw $e;
			}
			$this->assertContains($msg, $e->getMessage());
		}
	}

	public function testBBCodeAliasCanBeUsedForSettingTemplate()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b');
		$cb->addBBCodeAlias('b', 'strong');
		$cb->setBBCodeTemplate('strong', '<strong><xsl:apply-templates/></strong>');

		$text     = '[b]bold[/b] [strong]strong[/strong]';
		$expected = '<strong>bold</strong> <strong>strong</strong>';
		$actual   = $cb->getRenderer()->render($cb->getParser()->parse($text));

		$this->assertSame($expected, $actual);
	}

	public function BBCodeExamples()
	{
		return array(
			array(
				'[b]{TEXT}[/b]',
				'<b>{TEXT}</b>',
				null,
				'Some [b]bold[/b] text',
				'Some <b>bold</b> text'
			),
			array(
				'[email]{EMAIL}[/email]',
				'<a href="mailto:{EMAIL}">{EMAIL}</a>',
				null,
				'My email is [email]none@example.com[/email]',
				'My email is <a href="mailto:none@example.com">none@example.com</a>'
			),
			array(
				'[email={EMAIL}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}">{TEXT}</a>',
				null,
				'My email is [email=none@example.com]HERE[/email]',
				'My email is <a href="mailto:none@example.com">HERE</a>'
			),
			array(
				'[email]{TEXT}[/email]',
				'<a href="mailto:{TEXT}">{TEXT}</a>',
				null,
				'My email is [email]none@example.com[/email]',
				null,
				'ALLOW_INSECURE_TEMPLATES'
			),
			array(
				'[email]{TEXT}[/email]',
				'<a href="mailto:{TEXT}">{TEXT}</a>',
				ConfigBuilder::ALLOW_INSECURE_TEMPLATES,
				'My email is [email]COULD BE ANYTHING[/email]',
				'My email is <a href="mailto:COULD BE ANYTHING">COULD BE ANYTHING</a>'
			)
		);
	}
}