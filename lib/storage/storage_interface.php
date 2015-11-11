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

	/**
	 * Register hooks for settings form.
	 */
	public function register();

	/**
	 * Episode Form
	 * 
	 * @param  \Podlove\Model\Episode $episode
	 * @return array Form options
	 */
	// public function episode_form($episode);

	/**
	 * Initialize storage logic.
	 */
	public function init();
}
