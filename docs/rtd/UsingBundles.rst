Getting started using predefined bundles
========================================

The fastest way to start using s9e\TextFormatter is to use a preconfigured bundle. Below are two examples: the Forum bundle supports BBCodes and syntax commonly used in forums, and the Fatdown bundle supports most of Markdown plus automatic media embedding.

Forum bundle (BBCode)
---------------------

.. code-block:: php

	use s9e\TextFormatter\Bundles\Forum as TextFormatter;

	$text = 'To-do list:
	[list]
	  [*] Say hello to the world :)
	  [*] Go to http://example.com
	  [*] Try to trip the parser with [b]mis[i]nes[/b]ted[u] tags[/i][/u]
	  [*] Watch this video: [media]http://www.youtube.com/watch?v=QH2-TGUlwu4[/media]
	[/list]';

	// Parse the original text
	$xml = TextFormatter::parse($text);

	// Here you should save $xml to your database
	// $db->query('INSERT INTO ...');

	// Render and output the HTML result
	echo TextFormatter::render($xml);

	// You can "unparse" the XML to get the original text back
	assert(TextFormatter::unparse($xml) === $text);

.. code-block:: html

	To-do list:
	<ul>
	  <li> Say hello to the world <img src="/smile.png" alt=":)"></li>
	  <li> Go to <a href="http://example.com">http://example.com</a></li>
	  <li> Try to trip the parser with <b>mis<i>nes</i></b><i>ted<u> tags</u></i></li>
	  <li> Watch this video: <iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/QH2-TGUlwu4?controls=2"></iframe></li>
	</ul>


Fatdown bundle (Markdown)
-------------------------

.. code-block:: php

	use s9e\TextFormatter\Bundles\Fatdown as TextFormatter;

	$text = 'To-do list:

	  * Say hello to the world :)
	  * Go to http://example.com
	  * Try to trip the parser with **mis*nes**ted<u> tags*</u>
	  * Watch this video: http://www.youtube.com/watch?v=QH2-TGUlwu4';

	// Parse the original text
	$xml = TextFormatter::parse($text);

	// Here you should save $xml to your database
	// $db->query('INSERT INTO ...');

	// Render and output the HTML result
	echo TextFormatter::render($xml);

	// You can "unparse" the XML to get the original text back
	assert(TextFormatter::unparse($xml) === $text);

.. code-block:: html

	<p>To-do list:</p>

	  <ul><li>Say hello to the world :)</li>
	  <li>Go to <a href="http://example.com">http://example.com</a></li>
	  <li>Try to trip the parser with <strong>mis<em>nes</em></strong><em>ted<u> tags</u></em></li>
	  <li>Watch this video: <iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/QH2-TGUlwu4?controls=2"></iframe></li></ul>
