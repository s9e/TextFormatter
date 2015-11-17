<h2>Installation</h2>

s9e\\TextFormatter is developed on the latest version of PHP and is compatible with PHP 5.3.3 and above.

Once installed, you can try this [basic example](https://github.com/s9e/TextFormatter/blob/master/docs/examples/00_quick.php).

### Via Composer (preferred)

Install s9e\\TextFormatter using Composer's command line or by editing your `composer.json`. It will use Composer's autoloader normally.

#### Command-line

```bash
composer require s9e/text-formatter
```

#### composer.json

```json
{
    "require": {
        "s9e/text-formatter": "*"
    }
}
```

### Direct download

Download a snapshot of the library from GitHub directly: [php5.3](https://github.com/s9e/TextFormatter/archive/release/php5.3.zip), [php5.4](https://github.com/s9e/TextFormatter/archive/release/php5.4.zip), [php5.5](https://github.com/s9e/TextFormatter/archive/release/php5.5.zip), [php5.6](https://github.com/s9e/TextFormatter/archive/release/php5.6.zip). Unpack the archive, rename the directory to "TextFormatter" (optional, but it looks nicer) and use the bundled autoloader.

```php
include 'TextFormatter/src/autoloader.php';
```

### Via Git

Clone this repository and use the bundled autoloader. Replace `php5.3` with the lowest version of PHP you want to support: `php5.3`, `php5.4`, `php5.5` or `php5.6`. All versions are functionally identical.

```bash
git clone https://github.com/s9e/TextFormatter.git -b release/php5.3
```
```php
include 'TextFormatter/src/autoloader.php';
```
