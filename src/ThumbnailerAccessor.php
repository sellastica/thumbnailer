<?php
namespace Sellastica\Thumbnailer;

use Sellastica\Core\Model\FactoryAccessor;

/**
 * @method Thumbnailer get()
 */
class ThumbnailerAccessor extends FactoryAccessor
{
	/** @var IThumbnailApi */
	private $api;
	/** @var \Sellastica\Thumbnailer\IResourceUrlResolver */
	private $urlResolver;


	/**
	 * @param IThumbnailApi $api
	 * @param \Sellastica\Thumbnailer\IResourceUrlResolver $urlResolver
	 */
	public function __construct(
		IThumbnailApi $api,
		IResourceUrlResolver $urlResolver
	)
	{
		$this->api = $api;
		$this->urlResolver = $urlResolver;
	}

	/**
	 * @return Thumbnailer
	 */
	public function create(): Thumbnailer
	{
		return new Thumbnailer($this->api, $this->urlResolver);
	}
}
