<?php

declare(strict_types=1);

namespace Framework\tests\Collection;

use Framework\Collection\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testSetCollectionValue()
    {
        $collection = Collection::make(['test']);

        $this->assertEquals(['test'], $collection->all());
    }

    public function testLoopEachItem()
    {
        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl'])->map(function ($value, $index) {
            return "test{$index}";
        });

        $this->assertEquals(['test0', 'test1'], $collection->all());

        foreach ($collection as $key => $item) {
            $this->assertEquals("test{$key}", $item);
        }
    }

    public function testFilterOutItems()
    {
        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl'])->filter(function ($value, $index) {
            return $value === 'test';
        });

        $this->assertEquals(['test'], $collection->all());

        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl'])->filter(function ($value, $index) {
            return true;
        });

        $this->assertEquals(['test', 'ajsdfkjasldkfjkl'], $collection->all());
    }

    public function testFirstFromArray()
    {
        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl']);

        $this->assertEquals('test', $collection->first());
    }

    public function testLastFromArray()
    {
        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl']);

        $this->assertEquals('ajsdfkjasldkfjkl', $collection->last());
    }

    public function testSliceArray()
    {
        $collection = Collection::make(['test', 'ajsdfkjasldkfjkl']);

        $this->assertEquals(['test'], $collection->slice(0, 1)->all());
    }

    public function testCombine()
    {
        $collection = Collection::make(['a', 'b', 'c', 'd', 'e'])->combine([5, 4, 3, 2, 1]);

        $this->assertEquals([
            5 => 'a',
            4 => 'b',
            3 => 'c',
            2 => 'd',
            1 => 'e',
        ], $collection->all());
    }
}
