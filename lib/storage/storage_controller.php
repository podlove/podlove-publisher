<?php
namespace Podlove\Storage;

class StorageController {

	private $storages = [];
	const DEFAULT_STORAGE = 'wordpress';

	/**
	 * Current Storage Engine
	 * 
	 * @var \Podlove\Storage\StorageInterface
	 */
	private $current;

	public function __construct() {
		$storage_classes = apply_filters('podlove_storage_classes', []);
		foreach ($storage_classes as $storage_class) {
			$this->storages[] = new $storage_class;
		}
	}

	/**
	 * Register all storages
	 * 
	 * Must only be called once per request.
	 */
	public function register() {
		foreach ($this->storages as $storage) {
			$storage->register();
		}
	}

	public function setCurrent($storage_key) {
		
		if (empty($storage_key)) {
			$storage_key = self::DEFAULT_STORAGE;
		}

		foreach ($this->storages as $storage) {
			if ($storage::key() == $storage_key) {
				$this->current = $storage;
				return;
			}
		}

		throw new \Exception(sprintf('Podlove: Unknown storage key "%s"', $storage_key));
	}

	public function getCurrent() {
		return $this->current;
	}

}