Plugin     | Tag name | Priority | Notes
---------- | -------- | -------: | -----
Autoimage  | IMG      |       -1 |
Autolink   | URL      |        1 |
Autovideo  | VIDEO    |       -1 |
Emoji      | EMOJI    |       10 |
FancyPants | FP       |       10 | Only on single quotes
MediaEmbed | MEDIA    |      -10 |
Litedown   | CODE     |     -999 | Indented code block
Litedown   | CODE     |       -1 | Fenced code block
Litedown   | QUOTE    |     -999 | Increases with nesting level
Litedown   | URL      |        1 | Implicit reference, e.g. `[ref]`
Litedown   | URL      |       -1 | Reference-style, or inline
Litedown   | *i*      |     1000 | Start-of-line markup
Litedown   | *i*      |       -2 | References
PipeTables | TABLE    |       -4 |
PipeTables | THEAD    |       -3 |
PipeTables | TBODY    |       -3 |
PipeTables | TR       |       -2 |
PipeTables | TD       |       -1 |
PipeTables | TH       |       -1 |
PipeTables | *i*      |     1000 |
Preg       | \*       |     -100 |