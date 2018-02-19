<?php
namespace Sellastica\Thumbnailer;

use Sellastica\Thumbnailer\Exception\ThumbnailerException;

class Thumbnailer
{
	const CROP = 'crop',
		EXACT = 'exact',
		RESIZE = 'resize';

	/** @var IThumbnailApi */
	private $api;
	/** @var IResourceUrlResolver */
	private $urlResolver;


	/**
	 * @param IThumbnailApi $api
	 * @param IResourceUrlResolver $urlResolver
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
	 * @param string $url Absolute URL
	 * @param Options $options
	 * @param \Sellastica\Thumbnailer\WatermarkOptions|null $watermarkOptions
	 * @return string
	 */
	public function create(string $url, Options $options, WatermarkOptions $watermarkOptions = null): string
	{
		if (!$this->urlResolver->match($url)) {
			throw new ThumbnailerException(
				sprintf('Original image URL %s is not supported for creating thumbnails', $url)
			);
		}

		//remove timestamp and other queries from the file name
		$url = (new \Nette\Http\Url($url))->setQuery(null)->getAbsoluteUrl();
		$src = $this->urlResolver->getSrc($url);

		$sourceImage = new SourceImage($src, $url, $this->urlResolver->filemtime($src));
		$thumbnail = new Thumbnail($sourceImage, $options, $watermarkOptions, $this->api);

		if (!$thumbnail->isFresh() || (
				$watermarkOptions
				&& $watermarkOptions->getLastChangeTimestamp()
				&& $watermarkOptions->getLastChangeTimestamp() > $thumbnail->getTimestamp()
			)) {
			try {
				$thumbnail->increaseMemoryLimit();
				$thumbnail->generate();
				if ($watermarkOptions
					&& $watermarkOptions->useWatermark()
					&& $thumbnail->getImage()->getWidth() >= $watermarkOptions->getMinimalImageWidth()
					&& $thumbnail->getImage()->getHeight() >= $watermarkOptions->getMinimalImageHeight()) {
					$thumbnail->watermark();
				}

				$thumbnail->save();
				$thumbnail->destroyResourceImage();
				$thumbnail->restoreMemoryLimit();
			} catch (\Exception $e) {
				$thumbnail->restoreMemoryLimit();
				throw $e;
			}
		}

		return $thumbnail->getUrl()->getAbsoluteUrl();
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function createPlaceholder(?int $width, ?int $height)
	{
		return \Sellastica\Utils\Images::getPlaceholderUrl($width, $height);
	}
}
