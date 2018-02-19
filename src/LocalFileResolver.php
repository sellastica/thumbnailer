<?php
namespace Sellastica\Thumbnailer;

use Nette;
use Nette\Http\UrlScript;
use Sellastica\Http\FileUrl;

class LocalFileResolver implements IResourceUrlResolver
{
	/** @var UrlScript */
	private $refUrl;


	/**
	 * @param Nette\Http\Request $request
	 */
	public function __construct(Nette\Http\Request $request)
	{
		$this->refUrl = $request->getUrl();
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function match(string $url): bool
	{
		return (new UrlScript($url))->getHost() === $this->refUrl->getHost();
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function exists(string $url): bool
	{
		return is_file($this->getSrc($url));
	}

	/**
	 * @param string $src
	 * @return int|false
	 */
	public function filemtime(string $src)
	{
		return filemtime($src);
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function getSrc(string $url): string
	{
		return (new FileUrl($url))->getUrlFromDocumentRoot();
	}
}
