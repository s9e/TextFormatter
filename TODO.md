TODO
====

- add an option to enable/disable individual tags in Parser(?)
- finish the Fabric plugin
- create a plugin for the Markdown syntax (or possibly Upskirt/Redcarpet) and name it Downmark to feel witty and have a plugin for each letter from A to H
- write documentation, el oh el
- Add a rule to automatically create a parent to a tag, e.g. create <LIST> when <LI> is used. After creating <LIST>, add its id to the "require" field so that we don't get in an infinite loop
- Support forcing text to be wrapped inside tags. e.g. "<LIST>foo" becomes "<LIST><LI>foo</LI>"
- There's potential for a bug in createMatchingTag when creating a tag based on a tag that with trimBefore enabled. The position of the new tag should probably be the actual position of the tag, not of the whitespace
- Investigate the possibility of replacing autoClose with isEmpty
- JSParserGenerator: if all the plugins' RLA is the same, remove them from the config and use hints to bypass the if test
- Investigate the possibility of using an external CSS checker in order to enable a default "css" filter
- Consider a Twitter BBCode (https://dev.twitter.com/docs/embedded-tweets)
- Create a way for special XSL to be evaluated at the start/end of a rendering in order to embed resources such as external scripts
