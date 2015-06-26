<?php
namespace Podlove\PHP;

/**
 * @param array      $array
 * @param int|string $position
 * @param mixed      $insert
 */
function array_insert($array, $position, $insert)
{
	if (is_int($position)) {
		return array_splice($array, $position, 0, $insert);
    } else {
		$pos = array_search($position, array_keys($array));
		return array_merge(
			array_slice($array, 0, $pos),
			$insert,
			array_slice($array, $pos)
        );
    }
}