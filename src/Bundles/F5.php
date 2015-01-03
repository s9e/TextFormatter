<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Bundles;

abstract class F5 extends \s9e\TextFormatter\Bundle
{
	/**
	* @var s9e\TextFormatter\Parser Singleton instance used by parse()
	*/
	public static $parser;

	/**
	* @var s9e\TextFormatter\Renderer Singleton instance used by render() and renderMulti()
	*/
	public static $renderer;

	/**
	* Return a new instance of s9e\TextFormatter\Parser
	*
	* @return s9e\TextFormatter\Parser
	*/
	public static function getParser()
	{
		return unserialize("O:24:\"s9e\\TextFormatter\\Parser\":4:{s:16:\"\000*\000pluginsConfig\";a:4:{s:7:\"BBCodes\";a:5:{s:7:\"bbcodes\";a:21:{s:6:\"COLOUR\";a:1:{s:7:\"tagName\";s:5:\"COLOR\";}s:1:\"B\";a:0:{}s:1:\"I\";R:7;s:1:\"U\";R:7;s:1:\"S\";R:7;s:3:\"DEL\";R:7;s:3:\"INS\";R:7;s:2:\"EM\";R:7;s:5:\"COLOR\";R:7;s:1:\"H\";R:7;s:3:\"URL\";a:1:{s:17:\"contentAttributes\";a:1:{i:0;s:3:\"url\";}}s:5:\"EMAIL\";a:1:{s:17:\"contentAttributes\";a:1:{i:0;s:5:\"email\";}}s:5:\"TOPIC\";a:2:{s:17:\"contentAttributes\";a:1:{i:0;s:2:\"id\";}s:16:\"defaultAttribute\";s:2:\"id\";}s:4:\"POST\";R:14;s:5:\"FORUM\";R:14;s:4:\"USER\";R:14;s:3:\"IMG\";a:2:{s:17:\"contentAttributes\";a:1:{i:0;s:7:\"content\";}s:16:\"defaultAttribute\";s:3:\"alt\";}s:5:\"QUOTE\";a:1:{s:16:\"defaultAttribute\";s:6:\"author\";}s:4:\"CODE\";R:7;s:4:\"LIST\";a:1:{s:16:\"defaultAttribute\";s:4:\"type\";}s:1:\"*\";a:1:{s:7:\"tagName\";s:2:\"LI\";}}s:10:\"quickMatch\";s:1:\"[\";s:6:\"regexp\";s:111:\"#\\[/?([*BHS]|CO(?:DE|LOU?R)|DEL|EM(?>AIL)?|FORUM|I(?>MG|NS)?|LIST|POST|QUOTE|TOPIC|U(?>RL|SER)?)(?=[\\] =:/])#iS\";s:11:\"regexpLimit\";i:10000;s:17:\"regexpLimitAction\";s:4:\"warn\";}s:9:\"Emoticons\";a:4:{s:6:\"regexp\";s:80:\"/(?<!\\S)(?>:(?>[()DOP\\/op|]|cool:|lol:|mad:|rolleyes:)|;\\)|=[()D|])(?!\\pL\\pN)/Su\";s:7:\"tagName\";s:1:\"E\";s:11:\"regexpLimit\";i:10000;s:17:\"regexpLimitAction\";s:4:\"warn\";}s:9:\"Autoemail\";a:6:{s:8:\"attrName\";s:5:\"email\";s:10:\"quickMatch\";s:1:\"@\";s:6:\"regexp\";s:39:\"/\\b[-a-z0-9_+.]+@[-a-z0-9.]*[a-z0-9]/Si\";s:7:\"tagName\";s:5:\"EMAIL\";s:11:\"regexpLimit\";i:10000;s:17:\"regexpLimitAction\";s:4:\"warn\";}s:8:\"Autolink\";a:6:{s:8:\"attrName\";s:3:\"url\";s:10:\"quickMatch\";s:3:\"://\";s:6:\"regexp\";s:51:\"#(?:ftp|https?)://\\S(?>[^\\s\\[\\]]*(?>\\[\\w*\\])?)++#iS\";s:7:\"tagName\";s:3:\"URL\";s:11:\"regexpLimit\";i:10000;s:17:\"regexpLimitAction\";s:4:\"warn\";}}s:14:\"registeredVars\";a:1:{s:9:\"urlConfig\";a:1:{s:14:\"allowedSchemes\";s:20:\"/^(?:ftp|https?)\$/Di\";}}s:14:\"\000*\000rootContext\";a:3:{s:15:\"allowedChildren\";s:1:\"\007\";s:18:\"allowedDescendants\";s:1:\"\017\";s:5:\"flags\";i:40;}s:13:\"\000*\000tagsConfig\";a:21:{s:1:\"B\";a:7:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:42:\"s9e\\TextFormatter\\Parser::filterAttributes\";s:6:\"params\";a:4:{s:3:\"tag\";N;s:9:\"tagConfig\";N;s:14:\"registeredVars\";N;s:6:\"logger\";N;}}}s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:2;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:0;s:15:\"allowedChildren\";s:1:\"\005\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:1:\"I\";R:59;s:1:\"U\";R:59;s:1:\"S\";R:59;s:3:\"DEL\";a:7:{s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:256;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:0;s:15:\"allowedChildren\";s:1:\"\007\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:3:\"INS\";R:75;s:2:\"EM\";R:59;s:5:\"COLOR\";a:8:{s:10:\"attributes\";a:1:{s:5:\"color\";a:2:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:52:\"s9e\\TextFormatter\\Parser\\BuiltInFilters::filterColor\";s:6:\"params\";a:1:{s:9:\"attrValue\";N;}}}s:8:\"required\";b:1;}}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";R:69;s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:0;s:15:\"allowedChildren\";s:1:\"\005\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:1:\"H\";a:7:{s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:2052;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:1;s:15:\"allowedChildren\";s:1:\"\005\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:3:\"URL\";a:8:{s:10:\"attributes\";a:1:{s:3:\"url\";a:2:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:50:\"s9e\\TextFormatter\\Parser\\BuiltInFilters::filterUrl\";s:6:\"params\";a:3:{s:9:\"attrValue\";N;s:9:\"urlConfig\";N;s:6:\"logger\";N;}}}s:8:\"required\";b:1;}}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:258;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:2;s:15:\"allowedChildren\";s:1:\"\003\";s:18:\"allowedDescendants\";s:1:\"\013\";}s:5:\"EMAIL\";a:8:{s:10:\"attributes\";a:1:{s:5:\"email\";a:2:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:52:\"s9e\\TextFormatter\\Parser\\BuiltInFilters::filterEmail\";s:6:\"params\";R:89;}}s:8:\"required\";b:1;}}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";R:117;s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:2;s:15:\"allowedChildren\";s:1:\"\003\";s:18:\"allowedDescendants\";s:1:\"\013\";}s:5:\"TOPIC\";a:8:{s:10:\"attributes\";a:1:{s:2:\"id\";a:2:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:51:\"s9e\\TextFormatter\\Parser\\BuiltInFilters::filterUint\";s:6:\"params\";R:89;}}s:8:\"required\";b:1;}}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";R:117;s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:2;s:15:\"allowedChildren\";s:1:\"\003\";s:18:\"allowedDescendants\";s:1:\"\013\";}s:4:\"POST\";R:135;s:5:\"FORUM\";R:135;s:4:\"USER\";R:135;s:3:\"IMG\";a:8:{s:10:\"attributes\";a:2:{s:3:\"alt\";a:1:{s:8:\"required\";b:0;}s:7:\"content\";R:107;}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";R:77;s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:0;s:15:\"allowedChildren\";s:1:\"\007\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:5:\"QUOTE\";a:8:{s:10:\"attributes\";a:1:{s:6:\"author\";R:149;}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:3;s:5:\"rules\";a:1:{s:5:\"flags\";i:2060;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:1;s:15:\"allowedChildren\";s:1:\"\007\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:4:\"CODE\";a:7:{s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:2132;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:1;s:15:\"allowedChildren\";s:1:\"\000\";s:18:\"allowedDescendants\";s:1:\"\000\";}s:4:\"LIST\";a:8:{s:10:\"attributes\";a:1:{s:4:\"type\";a:2:{s:11:\"filterChain\";a:1:{i:0;a:2:{s:8:\"callback\";s:53:\"s9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp\";s:6:\"params\";a:2:{s:9:\"attrValue\";N;i:0;s:10:\"/^[a1]\$/Di\";}}}s:8:\"required\";b:0;}}s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:5;s:5:\"rules\";a:1:{s:5:\"flags\";i:3716;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:1;s:15:\"allowedChildren\";s:1:\"\010\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:2:\"LI\";a:7:{s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:2:{s:11:\"closeParent\";a:1:{s:2:\"LI\";i:1;}s:5:\"flags\";i:2056;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:3;s:15:\"allowedChildren\";s:1:\"\007\";s:18:\"allowedDescendants\";s:1:\"\017\";}s:1:\"E\";a:7:{s:11:\"filterChain\";R:60;s:12:\"nestingLimit\";i:10;s:5:\"rules\";a:1:{s:5:\"flags\";i:1537;}s:8:\"tagLimit\";i:1000;s:9:\"bitNumber\";i:0;s:15:\"allowedChildren\";s:1:\"\000\";s:18:\"allowedDescendants\";s:1:\"\017\";}}}");
	}

	/**
	* Return a new instance of s9e\TextFormatter\Renderer
	*
	* @return s9e\TextFormatter\Renderer
	*/
	public static function getRenderer()
	{
		return unserialize("O:37:\"s9e\\TextFormatter\\Bundles\\F5\\Renderer\":3:{s:13:\"\000*\000htmlOutput\";b:0;s:9:\"\000*\000params\";a:5:{s:8:\"BASE_URL\";s:0:\"\";s:12:\"IS_SIGNATURE\";s:0:\"\";s:7:\"L_WROTE\";s:6:\"wrote:\";s:8:\"SHOW_IMG\";s:1:\"1\";s:12:\"SHOW_IMG_SIG\";s:0:\"\";}s:18:\"metaElementsRegexp\";s:22:\"(<[eis]>[^<]*</[eis]>)\";}");
	}
}