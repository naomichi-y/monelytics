<?php
/**
 * @param collection $collection
 * @param string $target
 * @param string $delimiter
 * @return string
 */
function collection_to_string($collection, $target, $delimiter = ' / ')
{
  $string = '';

  foreach ($collection as $value) {
    $string .= $value->$target . $delimiter;
  }

  $string = rtrim($string, $delimiter);

  return $string;
}
