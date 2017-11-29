<?php
namespace Sellastica\Thumbnailer;

interface IResourceUrlResolver
{
	/**
	 * @param string $url
	 * @return bool
	 */
	function match(string $url): bool;

	/**
	 * @param string $url
	 * @return bool
	 */
	function exists(string $url): bool;

	/**
	 * @param string $url
	 * @return string
	 */
	function getSrc(string $url): string;

	/**
	 * @param string $src
	 * @return int|false
	 */
	function filemtime(string $src);

	/**
	 * @return bool
	 */
	function isLocal(): bool;
}
