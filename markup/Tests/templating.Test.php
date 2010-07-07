<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
//include_once __DIR__ . '/../parser.php';

class testTemplating extends \PHPUnit_Framework_TestCase
{
	/**
	* @expectedException InvalidArgumentException
	*/
	public function testInvalidXMLThrowsAnException()
	{
		$cb = new config_builder;
		$cb->addBBCode('b');
		$cb->setBBCodeTemplate('b', '<b><a></b>');
	}

	/**
	* @dataProvider BBCodeExamples
	*/
	public function testAddBBCodeFromExample($def, $tpl, $allow_insecure, $src, $expected, $msg = null)
	{
		$cb = new config_builder;

		try
		{
			$cb->addBBCodeFromExample($def, $tpl, $allow_insecure);
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
		catch (\PHPUnit_Framework_Error $e)
		{
			throw $e;
		}
		catch (\Exception $e)
		{
			$this->assertContains($msg, $e->getMessage());
		}
	}

	public function BBCodeExamples()
	{
		return array(

		array(
				'[b]{TEXT}[/b]',
				'<b>{TEXT}</b>',
				false,
				'Some [b]bold[/b] text',
				'Some <b>bold</b> text'
			),
			array(
				'[email]{EMAIL}[/email]',
				'<a href="mailto:{EMAIL}">{EMAIL}</a>',
				false,
				'My email is [email]none@example.com[/email]',
				'My email is <a href="mailto:none@example.com">none@example.com</a>'
			),
			array(
				'[email={EMAIL}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}">{TEXT}</a>',
				false,
				'My email is [email=none@example.com]HERE[/email]',
				'My email is <a href="mailto:none@example.com">HERE</a>'
			),
			array(
				'[email]{TEXT}[/email]',
				'<a href="mailto:{TEXT}">{TEXT}</a>',
				false,
				'My email is [email]none@example.com[/email]',
				null,
				'ALLOW_INSECURE_TEMPLATES'
			)
		);
	}
}