#!/usr/bin/php
<?php

foreach (glob(__DIR__ . '/*.convert.php') as $filepath)
{
	include $filepath;
}
