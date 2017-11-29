<?php
namespace Sellastica\Thumbnailer;

use Nette\Http\Request;
use Sellastica\Core\FactoryAccessor;
use Sellastica\Project\Model\SettingsAccessor;

class ThumbnailerAccessor extends FactoryAccessor
{
	/** @var IThumbnailApi */
	private $api;
	/** @var \Sellastica\Project\Model\SettingsAccessor */
	private $settingsAccessor;
	/** @var Request */
	private $request;


	/**
	 * @param IThumbnailApi $api
	 * @param \Sellastica\Project\Model\SettingsAccessor $settingsAccessor
	 * @param Request $request
	 */
	public function __construct(
		IThumbnailApi $api,
		SettingsAccessor $settingsAccessor,
		Request $request
	)
	{
		$this->api = $api;
		$this->settingsAccessor = $settingsAccessor;
		$this->request = $request;
	}

	/**
	 * @return Thumbnailer
	 */
	public function create(): Thumbnailer
	{
		$defaults = [
			'watermark_path' => WWW_DIR . '/img/watermark.png',
			'watermark_width' => $this->settingsAccessor->getSetting('thumbnail.watermarkWidth'),
			'watermark_height' => $this->settingsAccessor->getSetting('thumbnail.watermarkHeight'),
			'watermark_left' => $this->settingsAccessor->getSetting('thumbnail.watermarkLeft'),
			'watermark_top' => $this->settingsAccessor->getSetting('thumbnail.watermarkTop'),
		];
		$thumbnailer = new Thumbnailer($this->api, $defaults);
		$thumbnailer->addResourceResolver(new HttpFileResolver($this->request));
		$thumbnailer->addResourceResolver(new LocalFileResolver($this->request));

		return $thumbnailer;
	}
}
