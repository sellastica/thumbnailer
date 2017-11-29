<?php
namespace Sellastica\Thumbnailer;

class SourceImage implements IImage
{
	/** @var string */
	private $src;
	/** @var string */
	private $url;
	/** @var int */
	private $timestamp;
	/** @var bool */
	private $local;


	/**
	 * @param string $src
	 * @param string $url
	 * @param int $timestamp
	 * @param bool $local
	 */
	public function __construct(string $src, string $url, int $timestamp, bool $local)
	{
		$this->src = $src;
		$this->url = $url;
		$this->timestamp = $timestamp;
		$this->local = $local;
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
	 * @return int
	 */
	public function getTimestamp(): int
	{
		return $this->timestamp;
	}

	/**
	 * @return bool
	 */
	public function isLocal(): bool
	{
		return $this->local;
	}
}
