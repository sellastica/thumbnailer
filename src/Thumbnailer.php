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
	public function create(
		string $url,
		Options $options,
		WatermarkOptions $watermarkOptions = null
	): string
	{
		if (!$this->urlResolver->match($url)) {
			throw new ThumbnailerException(
				sprintf('Original image URL %s is not supported for creating thumbnails', $url)
			);
		}

		//remove timestamp and other queries from the file name
		$url = (new \Nette\Http\Url($url))->setQuery(null)->getAbsoluteUrl();
		if (!$this->urlResolver->exists($url)) {
			return $this->createPlaceholder($options->getWidth(), $options->getHeight());
		}

		$src = $this->urlResolver->getSrc($url);
		$sourceImage = new SourceImage($src, $url, $this->urlResolver->filemtime($src));
		$thumbnail = new Thumbnail($sourceImage, $options, $this->api);

		if (!$thumbnail->isFresh()) {
			try {
				$thumbnail->increaseMemoryLimit();
				$thumbnail->generate();
				if ($watermarkOptions
					&& $thumbnail->getImage()->getWidth() >= $watermarkOptions->getMinimalImageWidth()
					&& $thumbnail->getImage()->getHeight() >= $watermarkOptions->getMinimalImageHeight()) {
					$thumbnail->watermark($watermarkOptions);
				}

				$thumbnail->save();
				$thumbnail->destroyResourceImage();
				$thumbnail->restoreMemoryLimit();
			} catch (\Exception $e) {
				$thumbnail->restoreMemoryLimit();
				return $this->createPlaceholder($options->getWidth(), $options->getHeight());
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

	/**
	 * @return \Sellastica\Thumbnailer\IThumbnailApi
	 */
	public function getApi(): \Sellastica\Thumbnailer\IThumbnailApi
	{
		return $this->api;
	}
}
