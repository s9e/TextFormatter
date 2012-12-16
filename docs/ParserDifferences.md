Differences between the PHP parser and its Javascript port
==========================================================

 * #url filter
 * #email filter

Differences between the PHP parser and phpBB's
==============================================

 * Lots of cases (inside [code] mainly) where phpBB uses HTML entities to prevent text from being parsed
 * No server-side syntax highlighting
 * No filter available for local urls
 * No {INTTEXT} or {LOCAL_URL} tokens in BBCode definitions
 * [b ] gets parsed as [b] -- some people use that form to describe BBCodes and they would need to be escaped somehow, or put inside a [c] tag
 * No recursive parsing of attributes, IOW no [quote="[b]foo[/b]"] ([b] will be displayed as plain text)
