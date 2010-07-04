Overview
========

s9e\toolkit\markup is a text formatting tool. By default, it supports:

 * BBCode
 * emoticons
 * detection and replacement of banned words (censor)
 * detection of non-formatted links (autolink)

Each of those being completely customizable. In fact, there is no default BBCode or smilies so customization, or rather, configuration is a requirement.

It is designed to be fully extensible and could be used to parse other markup languages such as Markdown.


Technical design
----------------
We have 3 classes: config_builder, parser, and renderer.

**config_builder** will let you define which BBCode or smilies to parse, configure what links to format (by default, all http and https URLs but you can add other protocols to that list) and what words to censor. In return, it will give you an array containing your whole configuration, which you can serialize and store for later for better performance.

**parser** reads the original text and returns an intermediate XML representation, following your configuration. That processus is designed to be easily reversible.

**renderer** *(WiP)* transforms that XML into the HTML you want, either via string manipulation in PHP or via XSLT.


Implementation: parser
----------------------
Everything is a BBCode. At least internally.

BBCodes are BBcodes, smilies are BBCodes, censored words are BBCodes, etc... Here's how it works. Each pass declares a set of BBCodes with their position and length.

For instance, take the text `[b]xy :)[/b]`

 * the BBCode pass will declare
   + `<B>` (open BBCode B) at position 0, length 3
   + `</B>` (close BBCode B) at position 8, length 4
 * the smilies pass will declare
   + `<E />` (self-closed BBCode E) at position 6, length 2

It will have the same effect as if the original text was `<B>xy <E code=":)" /></B>`. Note that BBCode names B and E are independent of the name used in the original text. `[b]` could produce a BBCode named `<BOLD>`, I'm just saving a few bytes here. The same way, you define what BBCode to use for smilies using `config_builder::setEmoticonOption()`

Another, perhaps better example: `My start page is http://example.com`. Here, the autolink pass will declare

  * `<URL href="http://example.com">` at position 17, length 0
  * `</URL>` at position 35, length 0

In practice, some details may differ slightly, but you got the gist: everything is a BBCode. Once we have collected all the BBCodes, we sort them and process them in order. By making everything a BBCode, it's easy to define interactions between different passes. For instance, it's easy to disable emoticons inside of, say, `<CODE>` tags by disallowing `<E/>` inside of `<CODE/>`.

The length given in those examples is how many characters that BBCode will consume. Each character can only be used by one BBCode. Here, we see that `<E/>` will have exclusive use of `:)` whereas the autolink pass does not consume any character and simply adds 0-length tags around the URL. Therefore, this text can be used by other passes, such as the censor pass. You can then end up with something like

	My start page is <URL href="http://naughty.example.com">http://<CENSOR replacement="nice">naughty</CENSOR>.example.com</URL>

...which could then be rendered as this HTML:

	My start page is <a href="http://naughty.example.com">http://nice.example.com</a>
