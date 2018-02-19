<?php
namespace Sellastica\Thumbnailer;

class LocalStorageApi implements IThumbnailApi
{
	/** @var string */
	private $thumbnailRelativePath;
	/** @var \Nette\Http\UrlScript */
	private $refUrl;
	/** @var string */
	private $remoteOriginalRelativePath;


	/**
	 * @param string $thumbnailRelativePath
	 * @param string $remoteOriginalRelativePath
	 * @param \Nette\Http\IRequest $httpRequest
	 */
	public function __construct(
		string $thumbnailRelativePath,
		string $remoteOriginalRelativePath,
		\Nette\Http\IRequest $httpRequest
	)
	{
		$this->thumbnailRelativePath = $thumbnailRelativePath;
		$this->remoteOriginalRelativePath = $remoteOriginalRelativePath;
		$this->refUrl = $httpRequest->getUrl();
	}

	/**
	 * @param string $src
	 * @param int $timestampToCompare
	 * @return bool
	 */
	public function isFresh(string $src, int $timestampToCompare): bool
	{
		if (!is_file($src)) {
			return false;
		}

		$timestamp = $this->getTimestamp($src);
		return $timestamp !== false && $timestamp >= $timestampToCompare;
	}

	/**
	 * @param string $src
	 * @return int
	 */
	public function getTimestamp(string $src): int
	{
		return filemtime($src);
	}

	/**
	 * @param string $src
	 * @param \Nette\Utils\Image $image
	 * @throws \Nette\InvalidArgumentException
	 */
	public function save(string $src, \Nette\Utils\Image $image)
	{
		$this->createDir(dirname($src));
		$image->save($src, 100);
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
