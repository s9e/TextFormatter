#!/usr/bin/php
<?php

include __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';

$cb = new \s9e\Toolkit\TextFormatter\ConfigBuilder;

$cb->BBCodes->addPredefinedBBCode('B');
$cb->BBCodes->addPredefinedBBCode('I');
$cb->BBCodes->addPredefinedBBCode('U');
$cb->BBCodes->addPredefinedBBCode('S');
$cb->BBCodes->addPredefinedBBCode('URL');
$cb->BBCodes->addPredefinedBBCode('LIST');
$cb->BBCodes->addPredefinedBBCode('COLOR');

$jsParser = $cb->getJSParser(array(
	'compilation'     => 'ADVANCED_OPTIMIZATIONS',
//	'compilation'     => 'none',
	'disableLogTypes' => array(),
	'removeDeadCode'  => true
));

file_put_contents('/tmp/foo.js', $jsParser);

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title></title>
</head>
<body>
	<div>
		<form>
			<textarea></textarea>
		</form>
	</div>
	<pre></pre>

	<script type="text/javascript"><?php echo $jsParser ?>

		var text,
			textarea = document.getElementsByTagName('textarea')[0],
			pre = document.getElementsByTagName('pre')[0];

		window.setInterval(function()
		{
			if (textarea.value === text)
			{
				return;
			}

			text = textarea.value;

			var newPRE = document.createElement('pre');
			newPRE.appendChild(
				s9e.TextFormatter.render(
					s9e.TextFormatter.parse(text)
				)
			);

			pre.parentNode.replaceChild(newPRE, pre)
			pre = newPRE
		}, 50);
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../docs/JSParserDemo.html', ob_get_clean());

echo "Done.\n";