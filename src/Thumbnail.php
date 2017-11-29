<?php
namespace Sellastica\Thumbnailer;

use Nette\Http\Url;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;
use Sellastica\Http\FileUrl;
use Sellastica\Thumbnailer\Exception\ThumbnailerOptionsException;

class Thumbnail
{
	/** Memory limit in MB (int) */
	private const TEMPORARY_MEMORY_LIMIT = 512;

	/** @var Image|null */
	private $image;
	/** @var SourceImage */
	private $sourceImage;
	/** @var IThumbnailApi */
	private $api;
	/** @var array */
	private $options;
	/** @var string|null e.g. Image::JPEG, Image::PNG */
	private $format;

	/** @var string */
	private $relativeUrl;
	/** @var string */
	private $memoryLimit;


	/**
	 * @param SourceImage $sourceImage
	 * @param array $options
	 * @param IThumbnailApi $api
	 */
	public function __construct(SourceImage $sourceImage, array $options, IThumbnailApi $api)
	{
		$this->sourceImage = $sourceImage;
		$this->api = $api;
		$this->options = $options;
	}

	/**
	 * @return bool
	 */
	public function isFresh(): bool
	{
		return $this->api->isFresh($this->getSrc(), $this->sourceImage);
	}

	/**
	 * @return string
	 */
	public function getSrc(): string
	{
		return $this->api->getThumbnailSrc() . '/' . $this->getRelativeUrl();
	}

	/**
	 * @return int|null
	 */
	public function getTimestamp(): ?int
	{
		return $this->sourceImage->getTimestamp();
	}

	/**
	 * @return Url
	 */
	public function getUrl(): Url
	{
		$url = new Url($this->api->getThumbnailUrl() . '/' . $this->getRelativeUrl());
		$url->setQueryParameter(FileUrl::TIMESTAMP, $this->getTimestamp());
		return $url;
	}

	/**
	 * @return Image|null
	 */
	public function getImage(): ?Image
	{
		return $this->image;
	}

	/**
	 * @throws UnknownImageFileException
	 */
	public function generate()
	{
		$crop = isset($this->options['crop']) ? (bool)$this->options['crop'] : false;
		$enlarge = isset($this->options['enlarge']) ? (bool)$this->options['enlarge'] : false;
		$exact = isset($this->options['exact']) ? (bool)$this->options['exact'] : false;
		$width = $this->options['width'] ?? null;
		$height = $this->options['height'] ?? null;

		$image = Image::fromFile($this->sourceImage->getSrc());
		if ($crop) {
			$image->resize($width, $height, $enlarge ? Image::FILL : Image::EXACT);
			$this->image = $image;
		} elseif ($enlarge) {
			$image->resize($width, $height, Image::FILL);
			$this->image = $image;
		} elseif ($exact) {
			$image->resize($width, $height, Image::FIT | Image::SHRINK_ONLY);
			$blank = Image::fromBlank($width, $height, Image::rgb(255, 255, 255, 100));
			$blank->place($image, '50%', '50%');
			$this->image = $blank;
			$this->format = Image::PNG;
		} else {
			$image->resize($width, $height, Image::FIT | Image::SHRINK_ONLY);
			$this->image = $image;
		}
	}

	public function watermark()
	{
		$this->assertWatermarkOptions();
		if (!isset($this->image)) {
			$this->generate();
		}

		$watermarkImage = Image::fromFile($this->options['watermark_path']);
		if (substr($this->options['watermark_width'], -1) === '%') {
			$this->options['watermark_width'] = $this->options['width'] * $this->options['watermark_width'] / 100;
		}

		if (substr($this->options['watermark_height'], -1) === '%') {
			$this->options['watermark_height'] = $this->options['height'] * $this->options['watermark_height'] / 100;
		}

		$watermarkImage->resize($this->options['watermark_width'], $this->options['watermark_height']);
		$this->image->place($watermarkImage, $this->options['watermark_left'], $this->options['watermark_top']);
	}

	/**
	 * Frees image resource from memory
	 */
	public function destroyResourceImage()
	{
		if (isset($this->image)) {
			imagedestroy($this->image->getImageResource());
		}
	}

	public function increaseMemoryLimit()
	{
		$this->memoryLimit = ini_get('memory_limit');
		if ((int)$this->memoryLimit < self::TEMPORARY_MEMORY_LIMIT) {
			ini_set('memory_limit', self::TEMPORARY_MEMORY_LIMIT . 'M');
		}
	}

	public function restoreMemoryLimit()
	{
		if (isset($this->memoryLimit)) {
			ini_set('memory_limit', $this->memoryLimit);
		}
	}

	/**
	 * @return string
	 */
	private function getFileExtension(): string
	{
		if ($this->format) {
			switch ($this->format) {
				case Image::PNG:
					return 'png';
					break;
				case Image::JPEG:
					return 'jpg';
					break;
				case Image::GIF:
					return 'gif';
					break;
			}
		}

		return pathinfo($this->sourceImage->getUrl(), PATHINFO_EXTENSION);
	}

	/**
	 * @return string
	 */
	private function getFilename()
	{
		$baseName = pathinfo($this->sourceImage->getUrl(), PATHINFO_FILENAME);
		if (!empty($this->options['watermark'])) {
			$baseName .= '_w';
		}

		if (!empty($this->options['crop'])) {
			$baseName .= '_c';
		}

		if (!empty($this->options['enlarge'])) {
			$baseName .= '_e';
		}

		if (!empty($this->options['exact'])) {
			$baseName .= '_ex';
		}

		return $baseName . '.' . $this->getFileExtension();
	}

	/**
	 * @return string
	 */
	private function getRelativeUrl(): string
	{
		if (!isset($this->relativeUrl)) {
			$url = new Url($this->sourceImage->getUrl());
			$this->relativeUrl = $this->sourceImage->isLocal()
				? 'local'
				: 'remote/' . $url->getHost();

			$this->relativeUrl .= dirname($url->getPath());
			$this->relativeUrl .= '/' . $this->options['width'] . 'x' . $this->options['height'];
			$this->relativeUrl .= '/' . $this->getFilename();
		}

		return $this->relativeUrl;
	}

	/**
	 * @throws ThumbnailerOptionsException
	 */
	private function assertWatermarkOptions()
	{
		if (!isset($this->options['watermark_path'])
			|| !isset($this->options['watermark_width'])
			|| !isset($this->options['watermark_height'])
			|| !isset($this->options['watermark_top'])
			|| !isset($this->options['watermark_left'])
		) {
			throw new ThumbnailerOptionsException('Watermark options incomplete');
		}
	}
}
