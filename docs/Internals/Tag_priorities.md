Tags with a lower priority number are processed before those with a higher number.


Plugin     | Tag name | Priority | Notes
---------- | -------- | -------: | -----
Autoimage  | IMG      |        2 |
Autolink   | URL      |        1 |
Autolink   | v        |     1000 |
Autovideo  | VIDEO    |       -1 |
BBCodes    | *any*    |      -10 | BBCodes with suffix
Emoji      | EMOJI    |       10 |
FancyPants | FP       |       10 | Only on single quotes
MediaEmbed | MEDIA    |      -10 |
Litedown   | CODE     |     -999 | Indented code block
Litedown   | CODE     |       -1 | Fenced code block
Litedown   | QUOTE    |     -999 |
Litedown   | SPOILER  |     -999 |
Litedown   | URL      |        1 | Implicit reference, e.g. `[ref]`
Litedown   | URL      |       -1 | Reference-style, or inline
Litedown   | i        |     1000 | Start-of-line markup
PipeTables | TABLE    |     -104 |
PipeTables | THEAD    |     -103 |
PipeTables | TBODY    |     -103 |
PipeTables | TR       |     -102 |
PipeTables | TD       |     -101 |
PipeTables | TH       |     -101 |
PipeTables | i        |     1000 |
Preg       | *any*    |     -100 |