Any number of non-brackets characters with at most one level of balanced brackets.

    (?:[^\x17[\]]*(?:\[[^\x17[\]]*\])*)*


Any number of non-space, non-parenthesis characters with at most one level of balanced parentheses.

    (?:[^\x17\s()]*(?:\([^\x17\s()]*\))*)*


A string enclosed in double quotes, single quotes or parentheses.

    (?:"[^\x17"]*"|'[^\x17']*'|\([^\x17)]*\))


A link label consisting of at least one non-brackets character, within brackets.

    \[[^\x17[\]]+\]


An inline link, with optional title. The URL and title are separated by at least one space.

    \[(?:[^\x17[\]]*(?:\[[^\x17[\]]*\])*)*\]\(((?:[^\x17\s()]*(?:\([^\x17\s()]*\))*)*(?: +(?:"[^\x17"]*"|'[^\x17']*'|\([^\x17)]*\)))?)\)

