<?php

namespace Tagcade\Service\Fetcher\Fetchers;

use Tagcade\Service\Fetcher\FetcherInterface;

abstract class BaseFetcher implements FetcherInterface
{
	const TYPE = null;

	/**
	 * @inheritdoc
	 */
	public function supportType($type)
	{
		return $type = static::TYPE;
	}
}