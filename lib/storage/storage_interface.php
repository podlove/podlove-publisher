<?php
namespace Podlove\Storage;

interface StorageInterface {

	/**
	 * Media storage identifier
	 * 
	 * @return string
	 */
	public static function key();

	/**
	 * Media storage description
	 * 
	 * @return string
	 */
	public static function description();
}
