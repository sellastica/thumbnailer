<?php
namespace Sellastica\Thumbnailer;

use Nette\Utils\Image;

interface IThumbnailApi
{
	/**
	 * @return string
	 */
	function getThumbnailUrl(): string;

	/**
	 * @return string
	 */
	function getThumbnailSrc(): string;

	/**
	 * @param string $src
	 * @param SourceImage $sourceImage
	 * @return bool
	 */
	function isFresh(string $src, SourceImage $sourceImage): bool;

	/**
	 * @param string $url
	 * @param Image $image
	 * @return void
	 */
	function save(string $url, Image $image);
}
