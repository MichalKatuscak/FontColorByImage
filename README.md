FontColorByImage
================

Get contrast font color based on image in PHP

## Usign:

```php
<?php

require "FontColorByImage.class.php";

$url = "http://yousize.com/yourimage.png";
$fontColor = (new \Katuscak\FontColorByImage($url))->get();

print_r($fontColor);

/*
 * Array (
 * 	[r] => 160,
 * 	[g] => 123,
 * 	[b] => 255
 * )
 */
```

## Composer install

```
composer require katuscak/fontcolorbyimage dev-master
```

### Usign width Composer

Class will be loaded automatically with all others which have installed over Composer.

```php
<?php

require 'vendor/autoload.php';

$url = "http://yousize.com/yourimage.png";
$fontColor = (new \Katuscak\FontColorByImage($url))->get();

print_r($fontColor);

/*
 * Array (
 * 	[r] => 160,
 * 	[g] => 123,
 * 	[b] => 255
 * )
 */
```

