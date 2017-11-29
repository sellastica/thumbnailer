<?php
namespace Sellastica\Thumbnailer;

use Nette;
use Sellastica\Thumbnailer\Exception\InvalidArgumentException;
use Sellastica\Thumbnailer\Exception\ThumbnailApiException;
use Sellastica\Thumbnailer\Exception\ThumbnailerException;
use Sellastica\Thumbnailer\Exception\ThumbnailerOptionsException;
use Sellastica\Utils\Images;

class Thumbnailer
{
	/** @var IThumbnailApi */
	private $api;
	/** @var IResourceUrlResolver[] */
	private $resourceResolvers = [];
	/** @var array */
	private $defaults = [
		'watermark_width' => '25%',
		'watermark_height' => '25%',
		'watermark_left' => '50%',
		'watermark_top' => '50%',
	];

	/**
	 * @param IThumbnailApi $api
	 * @param array $defaults
	 */
	public function __construct(IThumbnailApi $api, array $defaults = [])
	{
		$this->api = $api;
		$this->defaults = array_merge($this->defaults, $defaults);
	}

	/**
	 * @param IResourceUrlResolver $resolver
	 */
	public function addResourceResolver(IResourceUrlResolver $resolver)
	{
		$this->resourceResolvers[] = $resolver;
	}

	/**
	 * @param string $url Absolute URL
	 * @param int $width
	 * @param int $height
	 * @return string
	 * @throws ThumbnailerException
	 */
	public function create(string $url, int $width = null, int $height = null): string
	{
		$options = [
			'width' => $width,
			'height' => $height,
		];
		for ($i = 3; $i < func_num_args(); $i++) {
			$options[func_get_arg($i)] = true;
		}

		if (!strlen($url)) {
			return Images::getPlaceholderUrl($options['width'], $options['height']);
		}

		$options = array_merge($this->defaults, $options);
		$this->assertDimensions($options);

		try {
			$this->assertUrl($url);
		} catch (InvalidArgumentException $e) {
			return $this->createPlaceholder($width, $height);
		}

		//remove timestamp and other queries from the file name
		$url = (new Nette\Http\Url($url))->setQuery(null)->getAbsoluteUrl();

		if (empty($this->resourceResolvers)) {
			throw new ThumbnailerException('Source image resolver missing');
		}

		foreach ($this->resourceResolvers as $resolver) {
			if ($resolver->match($url)) {
				if ($resolver->exists($url)) {
					$src = $resolver->getSrc($url);
					$sourceImage = new SourceImage($src, $url, $resolver->filemtime($src), $resolver->isLocal());
					break;
				} else {
					return Images::getPlaceholderUrl($options['width'], $options['height']);
				}
			}
		}

		if (!isset($sourceImage)) {
			return Images::getPlaceholderUrl($options['width'], $options['height']);
		}

		//thumbnail path and freshness check
		$thumbnail = new Thumbnail($sourceImage, $options, $this->api);
		if (!$thumbnail->isFresh()) {
			try {
				$thumbnail->increaseMemoryLimit();
				$thumbnail->generate();
				if (!empty($options['watermark'])) {
					try {
						$thumbnail->watermark();
					} catch (Nette\Utils\UnknownImageFileException $e) {
					}
				}

				$this->api->save($thumbnail->getSrc(), $thumbnail->getImage());
				$thumbnail->destroyResourceImage();
				$thumbnail->restoreMemoryLimit();
			} catch (Nette\Utils\UnknownImageFileException $e) {
				$thumbnail->restoreMemoryLimit();
				return Images::getPlaceholderUrl($options['width'], $options['height']);
			} catch (ThumbnailApiException $e) {
				$thumbnail->restoreMemoryLimit();
				return Images::getPlaceholderUrl($options['width'], $options['height']);
			}
		}

		return $thumbnail->getUrl()->getAbsoluteUrl();
	}

	/**
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function createPlaceholder(int $width, int $height)
	{
		$options = [
			'width' => $width,
			'height' => $height
		];
		for ($i = 3; $i < func_num_args(); $i++) {
			$options[func_get_arg($i)] = true;
		}

		return Images::getPlaceholderUrl($options['width'], $options['height']);
	}

	/**
	 * @param string $url
	 * @throws \Sellastica\Thumbnailer\Exception\InvalidArgumentException
	 */
	private function assertUrl(string $url)
	{
		if (!Nette\Utils\Validators::isUrl($url)) {
			throw new \Sellastica\Thumbnailer\Exception\InvalidArgumentException(sprintf('Image SRC must be an absolute URL, %s given', $url));
		}

		if (!pathinfo($url, PATHINFO_EXTENSION)) {
			throw new \Sellastica\Thumbnailer\Exception\InvalidArgumentException(sprintf('Unknown %s image extension', $url));
		}
	}

	/**
	 * @param array $options
	 * @throws \Sellastica\Thumbnailer\Exception\ThumbnailerOptionsException
	 */
	private function assertDimensions(array $options)
	{
		if (!isset($options['width']) && !isset($options['height'])) {
			throw new ThumbnailerOptionsException('At least one of width or height property must be defined');
		} elseif (isset($options['width']) && (!is_int($options['width']) || $options['width'] <= 0)) {
			throw new ThumbnailerOptionsException('Width must be a positive integer');
		} elseif (isset($options['height']) && (!is_int($options['height']) || $options['height'] <= 0)) {
			throw new \Sellastica\Thumbnailer\Exception\ThumbnailerOptionsException('Height must be a positive integer');
		}
	}
}
