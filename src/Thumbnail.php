<?php
namespace Sellastica\Thumbnailer;

use Nette\Http\Url;
use Nette\Utils\Image;
use Sellastica\Http\FileUrl;

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
	/** @var string|null e.g. Image::JPEG, Image::PNG */
	private $format;

	/** @var \Sellastica\Thumbnailer\Options */
	private $options;

	/** @var string */
	private $relativeUrl;
	/** @var string */
	private $memoryLimit;


	/**
	 * @param SourceImage $sourceImage
	 * @param \Sellastica\Thumbnailer\Options $options
	 * @param IThumbnailApi $api
	 */
	public function __construct(
		SourceImage $sourceImage,
		Options $options,
		IThumbnailApi $api
	)
	{
		$this->sourceImage = $sourceImage;
		$this->options = $options;
		$this->api = $api;
	}

	/**
	 * @return bool
	 */
	public function isFresh(): bool
	{
		return $this->api->isFresh($this->getSrc(), $this->sourceImage->getTimestamp());
	}

	/**
	 * @return int
	 */
	public function getTimestamp(): int
	{
		return $this->api->getTimestamp($this->getSrc());
	}

	/**
	 * @return string
	 */
	public function getSrc(): string
	{
		return $this->api->getThumbnailSrc() . '/' . $this->getRelativeUrl();
	}

	/**
	 * @return Url
	 */
	public function getUrl(): Url
	{
		$url = new Url($this->api->getThumbnailUrl() . '/' . $this->getRelativeUrl());
		$url->setQueryParameter(FileUrl::TIMESTAMP, $this->api->getTimestamp($this->getSrc()));
		return $url;
	}

	/**
	 * @return Image|null
	 */
	public function getImage(): ?Image
	{
		return $this->image;
	}

	public function generate(): void
	{
		$this->image = Image::fromFile($this->sourceImage->getSrc());

		//if no dimension is set
		if ($this->options->getWidth() === null 
			&& $this->options->getHeight() === null) {
			$this->options->setWidth($this->image->getWidth());
			$this->options->setHeight($this->image->getHeight());
		}

		switch ($this->options->getOperation()) {
			case Thumbnailer::CROP:
				$this->image->resize($this->options->getWidth(), $this->options->getHeight(), Image::EXACT);
				break;
			case Thumbnailer::EXACT:
				$this->image->resize($this->options->getWidth(), $this->options->getHeight(), Image::FIT | Image::SHRINK_ONLY);
				$blank = Image::fromBlank($this->options->getWidth(), $this->options->getHeight(), Image::rgb(255, 255, 255));
				$blank->place($this->image, '50%', '50%');
				$this->image = $blank;
				$this->format = Image::PNG;
				break;
			case Thumbnailer::RESIZE:
				$this->image->resize($this->options->getWidth(), $this->options->getHeight(), Image::FIT | Image::SHRINK_ONLY);
				break;
			default:
				throw new \Sellastica\Thumbnailer\Exception\ThumbnailerException('Uknown operation ' . $this->options->getOperation());
				break;
		}
	}

	/**
	 * @param \Sellastica\Thumbnailer\WatermarkOptions $watermarkOptions
	 */
	public function watermark(WatermarkOptions $watermarkOptions): void
	{
		try {
			$watermarkImage = Image::fromFile($watermarkOptions->getSrc());
			list($width, $height) = Image::calculateSize(
				$this->image->getWidth(),
				$this->image->getHeight(),
				$watermarkOptions->getWidth(),
				null
			);
			$watermarkImage->resize($width, $height);
			$this->image->place($watermarkImage, $watermarkOptions->getLeft(), $watermarkOptions->getTop());
		} catch (\Nette\Utils\ImageException $e) {
		}
	}

	/**
	 * Frees image resource from memory
	 */
	public function destroyResourceImage(): void
	{
		if (isset($this->image)) {
			@imagedestroy($this->image->getImageResource());
		}
	}

	public function increaseMemoryLimit(): void
	{
		$this->memoryLimit = ini_get('memory_limit');
		if ((int)$this->memoryLimit < self::TEMPORARY_MEMORY_LIMIT) {
			ini_set('memory_limit', self::TEMPORARY_MEMORY_LIMIT . 'M');
		}
	}

	public function restoreMemoryLimit(): void
	{
		if (isset($this->memoryLimit)) {
			ini_set('memory_limit', $this->memoryLimit);
		}
	}

	public function save(): void
	{
		$this->api->save($this->getSrc(), $this->image);
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
	private function getFilename(): string
	{
		$baseName = pathinfo($this->sourceImage->getUrl(), PATHINFO_FILENAME);
		switch ($this->options->getOperation()) {
			case Thumbnailer::CROP:
				$baseName .= '_c';
				break;
			case Thumbnailer::EXACT:
				$baseName .= '_ex';
				break;
			default:
				break;
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
			$this->relativeUrl = 'local' . dirname($url->getPath());
			$this->relativeUrl .= '/' . $this->options->getWidth() . 'x' . $this->options->getHeight();
			$this->relativeUrl .= '/' . $this->getFilename();
		}

		return $this->relativeUrl;
	}
}
