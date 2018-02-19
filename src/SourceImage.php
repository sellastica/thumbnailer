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


	/**
	 * @param string $src
	 * @param string $url
	 * @param int $timestamp
	 */
	public function __construct(string $src, string $url, int $timestamp)
	{
		$this->src = $src;
		$this->url = $url;
		$this->timestamp = $timestamp;
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
}
