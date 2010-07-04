Overview
========

s9e\toolkit\markup is a text formatting tool. By default, it supports:

 * BBCode
 * smilies (emoticons)
 * detection and replacement of banned words (censor)
 * detection of non-formatted links

Each of those being completely customizable. In fact, there is no default BBCode or smilies so customization, or rather, configuration is a requirement.

It is designed to be fully extensible and could be used to parse other markup languages such as Markdown.


Technical design
----------------
We have 3 classes: config_builder, parser, and renderer.

*config_builder* will let you define which BBCode or smilies to parse, configure what links to format (by default, all http and https URLs but you can add other protocols to that list) and what words to censor. In return, it will give you an array containing your whole configuration, which you can serialize and store for later for better performance.

*parser* reads the original text and returns an intermediate XML representation, following your configuration. That processus is designed to be easily reversible.

*renderer* (WiP) transforms that XML into the HTML you want, either via string manipulation in PHP or via XSLT.