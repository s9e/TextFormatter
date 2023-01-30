const tagName  = config.tagName,
      attrName = config.attrName,
      prio     = tagPriority || 0;

matches.forEach((m) =>
{
	addTagPair(tagName, m[0][1], 0, m[0][1] + m[0][0].length, 0, prio).setAttribute(attrName, m[0][0]);
});