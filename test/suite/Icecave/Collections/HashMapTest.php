<?php
namespace Icecave\Collections;

use Eloquent\Liberator\Liberator;
use Icecave\Collections\Iterator\Traits;
use PHPUnit_Framework_TestCase;
use stdClass;

class HashMapTest extends PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $collection = new HashMap;

        $collection->set('a', 1);
        $collection->set('b', 2);
        $collection->set('c', 3);

        $packet = serialize($collection);
        $unserializedCollection = unserialize($packet);

        $this->assertSame(
            Liberator::liberate($unserializedCollection)->elements,
            Liberator::liberate($collection)->elements
        );
    }

    /**
     * @group regression
     * @link https://github.com/IcecaveStudios/collections/issues/23
     */
    public function testSerializationOfHashFunction()
    {
        $collection = new HashMap(null, 'sha1');

        $packet = serialize($collection);
        $collection = unserialize($packet);

        $this->assertSame('sha1', Liberator::liberate($collection)->hashFunction);
    }
}
