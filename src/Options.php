<?php
namespace Sellastica\Thumbnailer;

class Options
{
	/** @var string */
	private $operation;
	/** @var int|null */
	private $width;
	/** @var int|null */
	private $height;


	/**
	 * @param string $operation
	 * @param int|null $width
	 * @param int|null $height
	 */
	public function __construct(
		string $operation,
		int $width = null,
		int $height = null
	)
	{
		$this->operation = $operation;
		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * @return string
	 */
	public function getOperation(): string
	{
		return $this->operation;
	}

	/**
	 * @return int|null
	 */
	public function getWidth(): ?int
	{
		return $this->width;
	}

	/**
	 * @param int|null $width
	 */
	public function setWidth(?int $width): void
	{
		$this->width = $width;
	}

	/**
	 * @return int|null
	 */
	public function getHeight(): ?int
	{
		return $this->height;
	}

	/**
	 * @param int|null $height
	 */
	public function setHeight(?int $height): void
	{
		$this->height = $height;
	}
}
