<?php

namespace OC\Core\Controller;

use OC\PreviewManager;
use OCP\AppFramework\Controller;
use OCP\Files\File;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;

class PreviewController extends Controller {

	/** @var string */
	private $userId;

	/** @var IRootFolder */
	private $root;

	/** @var IConfig */
	private $config;

	/** @var PreviewManager */
	private $previewManager;

	/** @var IAppData */
	private $appData;

	public function __construct($appName,
								IRequest $request,
								IRootFolder $root,
								IConfig $config,
								PreviewManager $previewManager,
								IAppData $appData,
								$userId
	) {
		parent::__construct($appName, $request);

		$this->previewManager = $previewManager;
		$this->root = $root;
		$this->config = $config;
		$this->appData = $appData;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $file
	 * @param int $x
	 * @param int $y
	 * @param bool $a
	 * @param bool $forceIcon
	 * @param string $mode
	 * @return DataResponse|Http\FileDisplayResponse
	 */
	public function getPreview(
		$file = '',
		$x = 32,
		$y = 32,
		$a = false,
		$forceIcon = true,
		$mode = 'fill') {

		if ($file === '') {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if ($x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$userFolder = $this->root->getUserFolder($this->userId);
			$file = $userFolder->get($file);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (!($file instanceof File) || (!$forceIcon && !$this->previewManager->isAvailable($file))) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} else if (!$file->isReadable()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$preview = new \OC\Preview\Generator(
			$this->root,
			$this->config,
			$this->previewManager,
			$file,
			$this->appData
		);

		$f = $preview->getPreview($x, $y, !$a, $mode);
		return new Http\FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
	}
}
