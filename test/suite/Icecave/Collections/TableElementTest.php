<?php
namespace Icecave\Collections;

use PHPUnit_Framework_TestCase;

class TableElementTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        list($this->_collection, $this->_table) = $this->collectionFixture();
    }

    protected function collectionFixture(array $collection = array('a' => 1, 'b' => 2, 'c' => 3))
    {
        $emptyElement = array_combine(
            array_keys($collection),
            array_fill(0, count($collection), null)
        );
        $table = Table::fromCollection(array(
            $emptyElement,
            $collection,
            $emptyElement,
        ));

        return array(
            $table->get(1),
            $table
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_table, $this->_collection->table());
        $this->assertSame(3, $this->_collection->size());
    }

    ///////////////////////////////////////////
    // Implementation of CollectionInterface //
    ///////////////////////////////////////////

    public function testSize()
    {
        $this->assertSame(3, $this->_collection->size());

        list($this->_collection) = $this->collectionFixture(array('a' => 1));

        $this->assertSame(1, $this->_collection->size());
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->_collection->isEmpty());
    }

    public function testToString()
    {
        $this->assertSame('<TableElement 3 ["a" => 1, "b" => 2, "c" => 3]>', $this->_collection->__toString());

        list($this->_collection) = $this->collectionFixture(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4));

        $this->assertSame('<TableElement 4 ["a" => 1, "b" => 2, "c" => 3, ...]>', $this->_collection->__toString());
    }

    //////////////////////////////////////////////////
    // Implementation of MutableCollectionInterface //
    //////////////////////////////////////////////////

    public function testClear()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support clear().'
        );
        $this->_collection->clear();
    }

    /////////////////////////////////////////
    // Implementation of IterableInterface //
    /////////////////////////////////////////

    public function testElements()
    {
        $this->assertSame(array(array('a', 1), array('b', 2), array('c', 3)), $this->_collection->elements());
    }

    public function testContains()
    {
        $this->assertFalse($this->_collection->contains(4));

        $this->_collection->set('a', 4);

        $this->assertTrue($this->_collection->contains(4));
    }

    public function testFiltered()
    {
        $this->_collection->set('b', null);

        $result = $this->_collection->filtered();

        $this->assertInstanceOf(__NAMESPACE__ . '\Map', $result);
        $this->assertSame(array(array('a', 1), array('c', 3)), $result->elements());
    }

    public function testFilteredWithPredicate()
    {
        list($this->_collection) = $this->collectionFixture(array(
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'd' => 4,
            'e' => 5,
        ));

        $result = $this->_collection->filtered(
            function ($key, $value) {
                return $value & 0x1;
            }
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Map', $result);
        $this->assertSame(array(array('a', 1), array('c', 3), array('e', 5)), $result->elements());
    }

    public function testMap()
    {
        $result = $this->_collection->map(
            function ($key, $value) {
                return array($key, $value + 1);
            }
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Map', $result);
        $this->assertSame(array(array('a', 2), array('b', 3), array('c', 4)), $result->elements());
    }

    ////////////////////////////////////////////////
    // Implementation of MutableIterableInterface //
    ////////////////////////////////////////////////

    public function testFilter()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support filter().'
        );
        $this->_collection->filter();
    }

    public function testApply()
    {
        $this->_collection->apply(
            function ($key, $value) {
                return $value + 1;
            }
        );

        $this->assertSame(array(array('a', 2), array('b', 3), array('c', 4)), $this->_collection->elements());
    }

    ////////////////////////////////////////////
    // Implementation of AssociativeInterface //
    ////////////////////////////////////////////

    public function testHasKey()
    {
        $this->assertTrue($this->_collection->hasKey('a'));
        $this->assertFalse($this->_collection->hasKey('d'));
    }

    public function testGet()
    {
        $this->assertSame(1, $this->_collection->get('a'));
    }

    public function testGetFailure()
    {
        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\UnknownKeyException',
            'Key "d" does not exist.'
        );
        $this->_collection->get('d');
    }

    public function testTryGet()
    {
        $value = '<not null>';

        $this->assertFalse($this->_collection->tryGet('d', $value));
        $this->assertSame('<not null>', $value); // element should not be changed on failure
        $this->assertTrue($this->_collection->tryGet('a', $value));
        $this->assertSame(1, $value);
    }

    public function testGetWithDefault()
    {
        $this->assertNull($this->_collection->getWithDefault('d'));
        $this->assertSame('<default>', $this->_collection->getWithDefault('d', '<default>'));
        $this->assertSame(1, $this->_collection->getWithDefault('a'));
    }

    public function testCascade()
    {
        $this->assertSame(2, $this->_collection->cascade('d', 'b', 'f'));
    }

    public function testCascadeFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "f" does not exist.');
        $this->_collection->cascade('d', 'e', 'f');
    }

    public function testCascadeWithDefault()
    {
        $this->assertSame('<default>', $this->_collection->cascadeWithDefault('<default>', 'd', 'e', 'f'));
        $this->assertSame(2, $this->_collection->cascadeWithDefault('<default>', 'd', 'b', 'f'));
    }

    public function testCascadeIterable()
    {
        $this->assertSame(2, $this->_collection->cascadeIterable(array('d', 'b', 'f')));
    }

    public function testCascadeIterableWithDefault()
    {
        $this->assertSame('<default>', $this->_collection->cascadeIterableWithDefault('<default>', array('d', 'e', 'f')));

        $this->assertSame(2, $this->_collection->cascadeIterableWithDefault('<default>', array('d', 'b', 'f')));
    }

    public function testKeys()
    {
        $this->assertSame(array('a', 'b', 'c'), $this->_collection->keys());
    }

    public function testValues()
    {
        $this->assertSame(array(1, 2, 3), $this->_collection->values());
    }

    public function testCombine()
    {
        $collection = new Map;
        $collection->set('a', 10);
        $collection->set('b', 20);
        $result = $this->_collection->combine($collection);

        $this->assertSame(array(array('a', 10), array('b', 20), array('c', 3)), $result->elements());
    }

    public function testProject()
    {
        $result = $this->_collection->project('b', 'd');

        $this->assertSame(array(array('b', 2)), $result->elements());
    }

    public function testProjectIterable()
    {
        $result = $this->_collection->projectIterable(array('b', 'd'));

        $this->assertSame(array(array('b', 2)), $result->elements());
    }

    ///////////////////////////////////////////////////
    // Implementation of MutableAssociativeInterface //
    ///////////////////////////////////////////////////

    public function testSet()
    {
        $this->assertSame(1, $this->_collection->get('a'));

        $this->_collection->set('a', 2);

        $this->assertSame(2, $this->_collection->get('a'));
    }

    public function testAdd()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support add().'
        );
        $this->_collection->add('d', 4);
    }

    public function testTryAdd()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support tryAdd().'
        );
        $this->_collection->tryAdd('d', 4);
    }

    public function testReplace()
    {
        $this->_collection->replace('a', 2);

        $this->assertSame(2, $this->_collection->get('a'));
    }

    public function testReplaceFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "d" does not exist.');
        $this->_collection->replace('d', 4);
    }

    public function testTryReplace()
    {
        $this->assertTrue($this->_collection->tryReplace('a', 2));

        $this->assertSame(2, $this->_collection->get('a'));

        $this->assertFalse($this->_collection->tryReplace('d', 2));
        $this->assertFalse($this->_collection->hasKey('d'));
    }

    public function testRemove()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support remove().'
        );
        $this->_collection->remove('a');
    }

    public function testTryRemove()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support tryRemove().'
        );
        $this->_collection->tryRemove('a');
    }

    public function testMerge()
    {
        $collection = new Map;
        $collection->set('a', 10);
        $collection->set('b', 20);
        $this->_collection->merge($collection);

        $this->assertSame(array(array('a', 10), array('b', 20), array('c', 3)), $this->_collection->elements());
    }

    public function testSwap()
    {
        $this->_collection->swap('a', 'b');

        $this->assertSame(array(array('a', 2), array('b', 1), array('c', 3)), $this->_collection->elements());
    }

    public function testSwapFailureWithUnknownSource()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "d" does not exist.');
        $this->_collection->swap('d', 'e');
    }

    public function testSwapFailureWithUnknownTarget()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "e" does not exist.');
        $this->_collection->swap('a', 'e');
    }

    public function testTrySwap()
    {
        $this->assertTrue($this->_collection->trySwap('a', 'b'));
        $this->assertSame(array(array('a', 2), array('b', 1), array('c', 3)), $this->_collection->elements());
    }

    public function testTrySwapFailureWithUnknownSource()
    {
        $this->assertFalse($this->_collection->trySwap('d', 'e'));
        $this->assertSame(array(array('a', 1), array('b', 2), array('c', 3)), $this->_collection->elements());
    }

    public function testTrySwapFailureWithUnknownTarget()
    {
        $this->assertFalse($this->_collection->trySwap('a', 'e'));
        $this->assertSame(array(array('a', 1), array('b', 2), array('c', 3)), $this->_collection->elements());
    }

    public function testMove()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support move().'
        );
        $this->_collection->move('a', 'b');
    }

    public function testTryMove()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support tryMove().'
        );
        $this->_collection->tryMove('a', 'b');
    }

    public function testRename()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support rename().'
        );
        $this->_collection->rename('a', 'b');
    }

    public function testTryRename()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support tryRename().'
        );
        $this->_collection->tryRename('a', 'b');
    }

    /////////////////////////////////
    // Implementation of Countable //
    /////////////////////////////////

    public function testCount()
    {
        $this->assertSame(3, count($this->_collection));
    }

    ////////////////////////////////
    // Implementation of Iterator //
    ////////////////////////////////

    public function testIteration()
    {
        $result = iterator_to_array($this->_collection);

        $this->assertSame(array('a' => 1, 'b' => 2, 'c' => 3), $result);
    }

    public function testManualIteration()
    {
        $this->_collection->rewind();

        $this->assertTrue($this->_collection->valid());
        $this->assertSame('a', $this->_collection->key());
        $this->assertSame(1, $this->_collection->current());

        $this->_collection->next();

        $this->assertTrue($this->_collection->valid());
        $this->assertSame('b', $this->_collection->key());
        $this->assertSame(2, $this->_collection->current());

        $this->_collection->next();

        $this->assertTrue($this->_collection->valid());
        $this->assertSame('c', $this->_collection->key());
        $this->assertSame(3, $this->_collection->current());

        $this->_collection->next();

        $this->assertFalse($this->_collection->valid());
        $this->assertNull($this->_collection->key());
        $this->assertNull($this->_collection->current());
    }

    ///////////////////////////////////
    // Implementation of ArrayAccess //
    ///////////////////////////////////

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->_collection['a']));
        $this->assertFalse(isset($this->_collection['d']));
    }

    public function testOffsetGet()
    {
        $this->assertSame(1, $this->_collection['a']);
    }

    public function testOffsetGetFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "d" does not exist.');
        $this->_collection['d'];
    }

    public function testOffsetSet()
    {
        $this->_collection['a'] = 4;

        $this->assertSame(array(array('a', 4), array('b', 2), array('c', 3)), $this->_collection->elements());
    }

    public function testOffsetUnset()
    {
        $this->setExpectedException(
            'LogicException',
            'Table elements do not support offsetUnset().'
        );
        unset($this->_collection['a']);
    }
}
