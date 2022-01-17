<?php

declare(strict_types=1);

namespace Framework\Tests\Cache;

use Framework\Cache\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
	public function testCreateCacheFolder()
	{
		new Cache(__DIR__ . '/../../cache');

		$this->assertEquals(true, file_exists(__DIR__ . '/../../cache'));
	}

	public function testStoreItem()
	{
		$cache = new Cache(__DIR__ . '/../../cache');

		$cache->data('This is an test etag', fn () => ['This is test data'], 30);

		$this->assertEquals(['This is test data'], $cache->get('This is an test etag'));
	}

	public function testRemoveItem()
	{
		$cache = new Cache(__DIR__ . '/../../cache');

		$cache->data('This is an test etag', fn () => ['This is test data'], 30);

		$this->assertEquals(['This is test data'], $cache->get('This is an test etag'));

		$cache->remove('This is an test etag');

		$this->assertEquals(false, $cache->get('This is an test etag'));
	}

	public function testFlushAllItems()
	{
		$cache = new Cache(__DIR__ . '/../../cache');

		$cache->data('This is an test etag1', fn () => ['This is test data1'], 0);
		$cache->data('This is an test etag2', fn () => ['This is test data2'], 0);
		$cache->data('This is an test etag3', fn () => ['This is test data3'], 0);

		$cache->flush();

		$this->assertEquals(false, $cache->get('This is an test etag1'));
		$this->assertEquals(false, $cache->get('This is an test etag2'));
		$this->assertEquals(false, $cache->get('This is an test etag3'));

		$cache->data('This is an test etag1', fn () => ['This is test data1'], 0);
		$cache->data('This is an test etag2', fn () => ['This is test data2'], 0);
		$cache->data('This is an test etag3', fn () => ['This is test data3'], 0);

		$cache->flushEndOfLiveTimeCacheItems();

		$this->assertEquals(false, $cache->get('This is an test etag1'));
		$this->assertEquals(false, $cache->get('This is an test etag2'));
		$this->assertEquals(false, $cache->get('This is an test etag3'));
	}

	public function __destruct()
	{
		if (file_exists(__DIR__ . '/../../cache')) {
			rmdir(__DIR__ . '/../../cache');
		}
	}
}
