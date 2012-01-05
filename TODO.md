TODO
====

- add an option to enable/disable individual tags in Parser(?)
- add an option to toggle whether a tag must be empty and/or can have an end tag
- finish the Fabric plugin
- create a plugin for the Markdown syntax (or possibly Upskirt/Redcarpet) and name it Downmark to feel witty and have a plugin for each letter from A to H
- write documentation, el oh el
- Add a rule to automatically create a parent to a tag, e.g. create <LIST> when <LI> is used. After creating <LIST>, add its id to the "require" field so that we don't get in an infinite loop
- Automatically merge duplicate templates, e.g. '<xsl:template match="B|STRONG"
- Support tags that must remain empty, such as <br/> -- grep for isEmpty
- Support forcing text to be wrapped inside tags. e.g. "<LIST>foo" becomes "<LIST><LI>foo</LI>"
- There's potential for a bug in createMatchingTag when creating a tag based on a tag that with trimBefore enabled. The position of the new tag should probably be the actual position of the tag, not of the whitespace
