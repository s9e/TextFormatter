<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\SimpleDOM\Tests;
use s9e\Toolkit\SimpleDOM\SimpleDOM;

include_once __DIR__ . '/../SimpleDOM.php';
 
class asPrettyXMLTest extends \PHPUnit_Framework_TestCase
{
	public function testGrandChildrenAreIndented()
	{
		$root = new SimpleDOM('<root><child1><grandchild1 /></child1><child2 /><child3 /></root>');

		$expected = '<?xml version="1.0"?>
<root>
  <child1>
    <grandchild1/>
  </child1>
  <child2/>
  <child3/>
</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testCommentsArePreserved()
	{
		$root = new SimpleDOM('<root><!--COMMENT--><child1 /><child2 /><child3 /></root>');

		$expected = '<?xml version="1.0"?>
<root>
  <!--COMMENT-->
  <child1/>
  <child2/>
  <child3/>
</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testPIsArePreserved()
	{
		$root = new SimpleDOM('<?xml-stylesheet type="text/xsl" href="foobar.xsl" ?><root><child1 /><child2 /><child3 /></root>');

		$expected = '<?xml version="1.0"?>
<?xml-stylesheet type="text/xsl" href="foobar.xsl" ?>
<root>
  <child1/>
  <child2/>
  <child3/>
</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testCDATASectionsArePreserved()
	{
		$root = new SimpleDOM('<root><child1 /><cdata><![CDATA[<foobar>]]></cdata><child2 /><child3 /></root>');

		$expected = '<?xml version="1.0"?>
<root>
  <child1/>
  <cdata><![CDATA[<foobar>]]></cdata>
  <child2/>
  <child3/>
</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testDoesNotInterfereWithTextNodes()
	{
		$root = new SimpleDOM('<root><child1>text<grandchild1 /></child1><child2 /><child3 /></root>');

		$expected = '<?xml version="1.0"?>
<root>
  <child1>text<grandchild1/></child1>
  <child2/>
  <child3/>
</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testWhitespaceNodesContainingLineFeedsArePreserved()
	{
		$root = new SimpleDOM('
			<root>
				<child1>text <b>bold</b> <i>italic</i></child1>
				<child2 />
				<child3 />
			</root>
		');

		$expected = '<?xml version="1.0"?>
<root>
				<child1>text <b>bold</b> <i>italic</i></child1>
				<child2/>
				<child3/>
			</root>
';

		$this->assertSame($expected, $root->asPrettyXML());
	}

	public function testFileIsCreatedOnSuccess()
	{
		$root = new SimpleDOM('<root><child /></root>');

		$filepath = $this->tempfile();
		$success  = $root->asPrettyXML($filepath);

		$this->assertTrue($success);
		$this->assertFileExists($filepath);
	}

	public function testFileContentMatchesXML()
	{
		$root = new SimpleDOM('<root><child /></root>');

		$filepath = $this->tempfile();
		$success  = $root->asPrettyXML($filepath);

		$this->assertXmlStringEqualsXmlFile($filepath, $root->asXML());
	}

	/**
	* Internal stuff
	*/
	protected $tempfiles = array();
	protected function tempfile()
	{
		$filepath = tempnam(sys_get_temp_dir(), 'SimpleDOM_test_');
		if (file_exists($filepath))
		{
			unlink($filepath);
		}

		$this->tempfiles[] = $filepath;
		return $filepath;
	}

	public function __destruct()
	{
		foreach ($this->tempfiles as $filepath)
		{
			if (file_exists($filepath))
			{
				unlink($filepath);
			}
		}
	}
}