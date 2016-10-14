<?php

namespace OC\Preview;

use OCP\Files\Node;
use OCP\Files\IRootFolder;

class WatcherConnector {

	/** @var IRootFolder */
	private $root;

	/**
	 * WatcherConnector constructor.
	 *
	 * @param IRootFolder $root
	 */
	public function __construct(IRootFolder $root) {
		$this->root = $root;
	}

	/**
	 * @return Watcher
	 */
	private function getWatcher() {
		return \OC::$server->query(Watcher::class);
	}

	public function connectWatcher() {
		$this->root->listen('\OC\Files', 'postWrite', function(Node $node) {
			$this->getWatcher()->postWrite($node);
		});

		$this->root->listen('\OC\Files', 'preDelete', function(Node $node) {
			$this->getWatcher()->preDelete($node);
		});

		$this->root->listen('\OC\Files', 'postDelete', function(Node $node) {
			$this->getWatcher()->postDelete($node);
		});
	}
}
