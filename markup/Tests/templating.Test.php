<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

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
}