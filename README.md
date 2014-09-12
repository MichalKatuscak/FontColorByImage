FontColorByImage
================

Get contrast font color based on image in PHP

Usign:

```php
<?php

include "FontColorByImage.class.php";

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
