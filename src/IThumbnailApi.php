<?php
namespace Sellastica\Thumbnailer;

interface IThumbnailApi
{
	/**
	 * @param string $src
	 * @return int
	 */
	function getTimestamp(string $src): int;

	/**
	 * @param string $src
	 * @param int $timestampToCompare
	 * @return bool
	 */
	function isFresh(string $src, int $timestampToCompare): bool;

	/**
	 * @param string $url
	 * @param \Nette\Utils\Image $image
	 * @return void
	 */
	function save(string $url, \Nette\Utils\Image $image);

	/**
	 * @return string
	 */
	function getThumbnailUrl(): string;

	/**
	 * @return string
	 */
	function getThumbnailSrc(): string;
}
