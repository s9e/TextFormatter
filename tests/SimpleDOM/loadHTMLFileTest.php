<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../../src/SimpleDOM/SimpleDOM.php';
 
class loadHTMLFileTest extends \PHPUnit_Framework_TestCase
{
	public function testWellFormedXML()
	{
		$html =
			'<html>
				<head>
					<title>Hello HTML</title>
				</head>
				<body>
					<p>Hello World!</p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTMLFile($this->file($html));

		$this->assertXmlStringEqualsXmlString($html, $node->asXML());
	}

	public function testFromValidHTMLMalformedXML()
	{
		$html =
			'<html>
				<head>
					<title>Hello HTML</title>
					<link rel="stylesheet" type="text/css" href="test.css">
				</head>
				<body>
					<p>Hello World!<br><font size=1>New line</font></p>
				</body>
			</html>';

		$xml =
			'<html>
				<head>
					<title>Hello HTML</title>
					<link rel="stylesheet" type="text/css" href="test.css" />
				</head>
				<body>
					<p>Hello World!<br /><font size="1">New line</font></p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTMLFile($this->file($html));

		$this->assertXmlStringEqualsXmlString($xml, $node->asXML());
	}

	public function testInvalidHTMLEntityAreSilentlyFixed()
	{
		$html =
			'<html>
				<body>
					<p><a href="test?a=1&b=2">link</a></p>
				</body>
			</html>';

		$xml =
			'<html>
				<body>
					<p><a href="test?a=1&amp;b=2">link</a></p>
				</body>
			</html>';

		$node = SimpleDOM::loadHTMLFile($this->file($html));

		$this->assertXmlStringEqualsXmlString($xml, $node->asXML());
	}

	/**
	* Internal stuff
	*/
	protected function file($contents)
	{
		$this->filepath = sys_get_temp_dir() . '/SimpleDOM_TestCase_loadHTMLFile.html';

		file_put_contents(
			$this->filepath,
			$contents
		);

		return $this->filepath;
	}

	public function tearDown()
	{
		unlink($this->filepath);
	}
}