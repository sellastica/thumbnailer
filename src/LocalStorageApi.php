<?php
namespace Sellastica\Thumbnailer;

use Nette;
use Nette\Http\UrlScript;
use Nette\Utils\Image;

class LocalStorageApi implements IThumbnailApi
{
	/** @var string */
	private $thumbnailRelativePath;
	/** @var UrlScript */
	private $refUrl;


	/**
	 * @param string $thumbnailRelativePath
	 * @param Nette\Http\Request $request
	 */
	public function __construct(
		string $thumbnailRelativePath,
		Nette\Http\Request $request
	)
	{
		$this->thumbnailRelativePath = $thumbnailRelativePath;
		$this->refUrl = $request->getUrl();
	}

	/**
	 * @return string
	 */
	public function getThumbnailUrl(): string
	{
		return $this->refUrl->getHostUrl() . '/' . $this->thumbnailRelativePath;
	}

	/**
	 * @return string
	 */
	public function getThumbnailSrc(): string
	{
		return WWW_DIR . '/' . $this->thumbnailRelativePath;
	}

	/**
	 * @param string $src
	 * @param SourceImage $sourceImage
	 * @return bool
	 */
	public function isFresh(string $src, SourceImage $sourceImage): bool
	{
		if (!is_file($src)) {
			return false;
		}

		$filemtime = filemtime($src);
		return $filemtime !== false && $filemtime >= $sourceImage->getTimestamp();
	}

	/**
	 * @param string $src
	 * @param Image $image
	 */
	public function save(string $src, Image $image)
	{
		$this->createDir(dirname($src));
		$image->save($src, 100);
	}

	/**
	 * @param string $dir
	 * @return void
	 * @throws \RuntimeException
	 */
	private function createDir(string $dir)
	{
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0755, true)) {
				throw new \RuntimeException("Could not create dir $dir. Check parent folder permissions");
			}
		}
	}
}
