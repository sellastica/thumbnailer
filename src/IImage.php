<?php
namespace Sellastica\Thumbnailer;

interface IImage
{
	/**
	 * @return string
	 */
	function getUrl(): string;

	/**
	 * @return string
	 */
	function getSrc(): string;
}
