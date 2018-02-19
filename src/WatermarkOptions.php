<?php
namespace Sellastica\Thumbnailer;

class WatermarkOptions
{
	/** @var string */
	private $url;
	/** @var int|string */
	private $width;
	/** @var int|string */
	private $left;
	/** @var int|string */
	private $top;
	/** @var int */
	private $minimalImageWidth;
	/** @var int */
	private $minimalImageHeight;
	/** @var string */
	private $src;
	/** @var int|null */
	private $lastChangeTimestamp;
	/** @var bool */
	private $useWatermark;


	/**
	 * @param string $src
	 * @param string $url
	 * @param bool $useWatermark
	 * @param string|int $width
	 * @param string|int $left
	 * @param string|int $top
	 * @param int|null $lastChangeTimestamp
	 * @param int $minimalImageWidth
	 * @param int $minimalImageHeight
	 * @throws \Sellastica\Thumbnailer\Exception\ThumbnailerException
	 */
	public function __construct(
		string $src,
		string $url,
		bool $useWatermark,
		$width,
		$left,
		$top,
		int $lastChangeTimestamp = null,
		int $minimalImageWidth = 0,
		int $minimalImageHeight = 0
	)
	{
		$this->assertWidth($width);
		$this->assertOption('watermak_width', $width);
		$this->assertOption('watermak_left', $left);
		$this->assertOption('watermak_top', $top);

		$this->url = $url;
		$this->width = $width;
		$this->left = $left;
		$this->top = $top;
		$this->src = $src;
		$this->minimalImageWidth = $minimalImageWidth;
		$this->minimalImageHeight = $minimalImageHeight;
		$this->lastChangeTimestamp = $lastChangeTimestamp;
		$this->useWatermark = $useWatermark;
	}

	/**
	 * @return string
	 */
	public function getSrc(): string
	{
		return $this->src;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return int|string
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @return int|string
	 */
	public function getLeft()
	{
		return $this->left;
	}

	/**
	 * @return int|string
	 */
	public function getTop()
	{
		return $this->top;
	}

	/**
	 * @return int|null
	 */
	public function getLastChangeTimestamp(): ?int
	{
		return $this->lastChangeTimestamp;
	}

	/**
	 * @return int
	 */
	public function getMinimalImageWidth(): int
	{
		return $this->minimalImageWidth;
	}

	/**
	 * @return int
	 */
	public function getMinimalImageHeight(): int
	{
		return $this->minimalImageHeight;
	}

	/**
	 * @return bool
	 */
	public function useWatermark(): bool
	{
		return $this->useWatermark;
	}

	/**
	 * @param $width
	 * @throws \Sellastica\Thumbnailer\Exception\ThumbnailerException
	 */
	private function assertWidth($width): void
	{
		if ((int)$width <= 0) {
			throw new Exception\ThumbnailerException("Width must be eighter numeric or percentual value greater than zero");
		}
	}

	/**
	 * Value can be null, numeric or percentual
	 * @param string $name
	 * @param $value
	 */
	private function assertOption(string $name, $value): void
	{
		if (substr($value, -1) !== '%'
			&& !is_numeric($value)) {
			throw new Exception\ThumbnailerException("$name must be eighter numeric or percentual value");
		}
	}
}
