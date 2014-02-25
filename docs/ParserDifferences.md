Differences between the PHP parser and its JavaScript port
==========================================================

 * #url filter (no punycode)
 * #email filter (non-validating regexp, only catches the most egregious errors)
 * HTMLEntities might convert a slightly different set of HTML entities due to the differences between each browser's translation table and PHP's

Differences between the PHP parser and phpBB's
==============================================

 * Lots of cases (inside [code] mainly) where phpBB uses HTML entities to prevent text from being parsed
 * No server-side syntax highlighting -- but this could be implemented as a custom plugin
 * No filter available for local urls -- but this could be implemented as a custom attribute filter
 * No {INTTEXT} or {LOCAL_URL} tokens in BBCode definitions -- unless you define the corresponding custom filters
 * [b ] gets parsed as [b] -- some people use that form to describe BBCodes and they would need to be escaped somehow, or put inside a [c] tag
 * No recursive parsing of attributes, IOW no [quote="[b]foo[/b]"] ([b] will be displayed as plain text) -- could be implemented as a tag filter which uses its own Parser instance (Parser is not reentrant) and stores the parsed author name as a data: URI containing its intermediate representation which would be rendered via <xsl:apply-templates select="document(@data-uri)"/>
 * Different rules for emoticons, censored words, autolinking
 * Censor is run at posting time, whenever the censor list is changed, posts containing the word that has been added or removed would need to be updated (could use the search backend to find them)
 * Accepts any valid URL for images. Does not test image size. Can be implemented as a custom filter
