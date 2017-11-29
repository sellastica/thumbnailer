<?php
namespace Sellastica\Thumbnailer;

use Nette\Http\Request;
use Nette\Http\UrlScript;

class HttpFileResolver implements IResourceUrlResolver
{
	private $cache = [];
	/** @var UrlScript */
	private $refUrl;

	/**
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		$this->refUrl = $request->getUrl();
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function match(string $url): bool
	{
		$url = new UrlScript($url);
		return $url->getHost() !== $this->refUrl->getHost()
			&& in_array($url->getScheme(), ['http', 'https']);
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function exists(string $url): bool
	{
		return !empty($this->getFileHeaders($url));
	}

	/**
	 * @param string $url
	 * @return int|false
	 */
	public function filemtime(string $url)
	{
		$filemtime = $this->getFileHeaders($url);
		return $filemtime ?: null;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function getSrc(string $url): string
	{
		return $url;
	}

	/**
	 * @return bool
	 */
	public function isLocal(): bool
	{
		return false;
	}

	/**
	 * @param $url
	 * @return mixed
	 */
	private function getFileHeaders($url)
	{
		$hash = md5($url);
		if (!isset($this->cache[$hash])) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_FILETIME, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$this->cache[$hash] = curl_exec($ch) !== false
				? curl_getinfo($ch, CURLINFO_FILETIME)
				: false;

			curl_close($ch);
		}
		
		return $this->cache[$hash];
	}
}
