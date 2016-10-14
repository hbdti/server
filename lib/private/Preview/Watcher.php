<?php

namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

/**
 * Class Watcher
 *
 * @package OC\Preview
 *
 * Class that will watch filesystem activity and remove previews as needed.
 */
class Watcher {
	/** @var IAppData */
	private $appData;

	/** @var int[] */
	private $toDelete = [];

	/**
	 * Watcher constructor.
	 *
	 * @param IAppData $appData
	 */
	public function __construct(IAppData $appData) {
		$this->appData = $appData;
	}

	public function postWrite(Node $node) {
		// We only handle files
		if ($node instanceof Folder) {
			return;
		}

		try {
			$folder = $this->appData->getFolder($node->getId());
			$folder->delete();
		} catch (NotFoundException $e) {
			//Nothing to do
		}
	}

	public function preDelete(Node $node) {
		// To avoid cycles
		if ($this->toDelete !== []) {
			return;
		}

		if ($node instanceof File) {
			$this->toDelete[] = $node->getId();
			return;
		}

		/** @var Folder $node */
		$nodes = $node->search('');
		foreach ($nodes as $node) {
			if ($node instanceof File) {
				$this->toDelete[] = $node->getId();
			}
		}
	}

	public function postDelete(Node $node) {
		foreach ($this->toDelete as $fid) {
			try {
				$folder = $this->appData->getFolder($fid);
				$folder->delete();
			} catch (NotFoundException $e) {
				// continue
			}
		}
	}
}
