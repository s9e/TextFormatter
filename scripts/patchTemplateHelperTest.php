#!/usr/bin/php
<?php

class PHPUnit_Framework_TestCase {}

include __DIR__ . '/../src/autoloader.php';

$test = new s9e\TextFormatter\Tests\ConfigBuilder\TemplateHelperTest;

$php = '';
foreach ($test->getUnsafeTags() as $case)
{
	$attributeInfo = '';

	if (isset($case[2]))
	{
		$attributes = $case[2]['attributes'];
		$attrName   = key($attributes);
		$attribute  = $attributes[$attrName];
		$filter     = $attribute['filterChain'][0];

		$attributeInfo = " if attribute '$attrName' has filter " . var_export($filter, true);

		if (isset($attribute['regexp']))
		{
			$attributeInfo .= ' with regexp ' . $attribute['regexp'];
		}
	}

	$php .= "\n\t/**\n\t* @testdox checkUnsafe() identifies " . $case[1] . " as "
	      . (($case[0] === false) ? 'safe' : 'unsafe')
	      . $attributeInfo
	      . "\n\t*/"
	      . "\n\tpublic function testCheckUnsafe"
	      . sprintf('%08X', crc32(serialize(array_slice($case, 1))))
	      . "()\n\t{\n\t\t\$this->testUnsafeTags("
	      . "\n\t\t\t" . var_export($case[0], true)
	      . ','
	      . "\n\t\t\t" . var_export($case[1], true)
	      . ((isset($case[2])) ? ",\n\t\t\t" . var_export($case[2], true) : '')
	      . "\n\t\t);\n\t}\n";
}

$filepath = __DIR__ . '/../tests/ConfigBuilder/TemplateHelperTest.php';
$file = file_get_contents($filepath);

$startComment = '// Start of content generated by ../scripts/' . basename(__FILE__);
$endComment = "\t// End of content generated by ../scripts/" . basename(__FILE__);

$file = substr($file, 0, strpos($file, $startComment) + strlen($startComment))
      . $php
      . substr($file, strpos($file, $endComment));

file_put_contents($filepath, $file);

die("Done.\n");