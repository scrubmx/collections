<?php
namespace Icecave\Collections;

use ArrayIterator;
use Phake;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->_collection = new Table(
            array('A', 'B')
        );
    }

    protected function tableElementToArray($element)
    {
        if (!is_array($element)) {
            $element = iterator_to_array($element);
        }

        return $element;
    }

    protected function tableElementsToArray($elements)
    {
        $elementsArray = array();
        foreach ($elements as $index => $element) {
            $elementsArray[$index] = $this->tableElementToArray($element);
        }

        return $elementsArray;
    }

    protected function assertTableElement($expected, $actual)
    {
        $this->assertSame(
            $this->tableElementToArray($expected),
            $this->tableElementToArray($actual)
        );
    }

    protected function assertTableElements($expected, $actual)
    {
        $this->assertSame(
            $this->tableElementsToArray($expected),
            $this->tableElementsToArray($actual)
        );
    }

    public function testConstructor()
    {
        $this->assertSame(array('A', 'B'), $this->_collection->columnNames());
        $this->assertSame(2, $this->_collection->elementSize());
        $this->assertSame(0, $this->_collection->size());
    }

    public function testConstructorWithArray()
    {
        $elements = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );
        $this->_collection = new Table(
            array('A', 'B'),
            $elements
        );

        $this->assertTableElements($elements, $this->_collection->elements());
    }

    public function testConstructorWithIterableColumnNames()
    {
        $columnNames = Phake::mock(__NAMESPACE__ . '\IterableInterface');
        Phake::when($columnNames)
            ->elements(Phake::anyParameters())
            ->thenReturn(array('A', 'B'))
        ;
        $this->_collection = new Table(
            $columnNames
        );

        $this->assertSame(array('A', 'B'), $this->_collection->columnNames());
    }

    public function testConstructorWithIteratorColumnNames()
    {
        $columnNames = new ArrayIterator(array('A', 'B'));
        $this->_collection = new Table(
            $columnNames
        );

        $this->assertSame(array('A', 'B'), $this->_collection->columnNames());
    }

    public function testConstructorFailureEmptyColumnNames()
    {
        $this->setExpectedException(
            'LogicException',
            'Tables must have at least one column.'
        );
        new Table(array());
    }

    public function testClone()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $collection = clone $this->_collection;
        $collection->popBack();
        $expectedClone = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );
        $expectedOriginal = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expectedClone, $collection->elements());
        $this->assertTableElements($expectedOriginal, $this->_collection->elements());
    }

    public function testSerialization()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));

        $packet = serialize($this->_collection);
        $collection = unserialize($packet);

        $this->assertTableElements($this->_collection->elements(), $collection->elements());
    }

    ///////////////////////////////////////////
    // Implementation of CollectionInterface //
    ///////////////////////////////////////////

    public function testSize()
    {
        $this->assertSame(0, $this->_collection->size());

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));

        $this->assertSame(3, $this->_collection->size());

        $this->_collection->clear();

        $this->assertSame(0, $this->_collection->size());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->_collection->isEmpty());

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->assertFalse($this->_collection->isEmpty());

        $this->_collection->clear();

        $this->assertTrue($this->_collection->isEmpty());
    }

    public function testToString()
    {
        $this->_collection = new Table(array('A', 'B', 'C'));

        $this->assertSame('<Table 0x3 [A, B, C]>', $this->_collection->__toString());

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0', 'C' => 'C0'));

        $this->assertSame('<Table 1x3 [A, B, C]>', $this->_collection->__toString());

        $this->_collection = new Table(array('A', 'B', 'C', 'D'));

        $this->assertSame('<Table 0x4 [A, B, C, ...]>', $this->_collection->__toString());

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0', 'C' => 'C0', 'D' => 'D0'));

        $this->assertSame('<Table 1x4 [A, B, C, ...]>', $this->_collection->__toString());
    }

    //////////////////////////////////////////////////
    // Implementation of MutableCollectionInterface //
    //////////////////////////////////////////////////

    public function testClear()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->_collection->clear();

        $this->assertTrue($this->_collection->isEmpty());
    }

    /////////////////////////////////////////
    // Implementation of IterableInterface //
    /////////////////////////////////////////

    public function testElements()
    {
        $this->assertSame(array(), $this->_collection->elements());

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testContains()
    {
        $this->assertFalse($this->_collection->contains('foo'));

        $element = array('A' => 'A0', 'B' => 'B0');

        $this->assertFalse($this->_collection->contains($element));

        $this->_collection->pushBack($element);

        $this->assertTrue($this->_collection->contains($element));

        $element = new ArrayIterator($element);

        $this->assertTrue($this->_collection->contains($element));
    }

    public function testFiltered()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => null, 'B' => null));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => null, 'B' => null));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $result = $this->_collection->filtered();
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    public function testFilteredWithPredicate()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A5', 'B' => 'B5'));
        $keep = true;
        $result = $this->_collection->filtered(
            function ($element) use (&$keep) {
                return $keep = !$keep;
            }
        );
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A5', 'B' => 'B5'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    public function testMap()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $result = $this->_collection->map(
            function ($element) {
                return array_map('strtolower', iterator_to_array($element));
            }
        );
        $expected = array(
            array('A' => 'a0', 'B' => 'b0'),
            array('A' => 'a1', 'B' => 'b1'),
            array('A' => 'a2', 'B' => 'b2'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    ////////////////////////////////////////////////
    // Implementation of MutableIterableInterface //
    ////////////////////////////////////////////////

    public function testFilter()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => null, 'B' => null));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => null, 'B' => null));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->filter();
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testFilterWithPredicate()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A5', 'B' => 'B5'));
        $keep = true;
        $this->_collection->filter(
            function ($element) use (&$keep) {
                return $keep = !$keep;
            }
        );
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A5', 'B' => 'B5'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testApply()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->apply(
            function ($element) {
                return array_map('strtolower', iterator_to_array($element));
            }
        );
        $expected = array(
            array('A' => 'a0', 'B' => 'b0'),
            array('A' => 'a1', 'B' => 'b1'),
            array('A' => 'a2', 'B' => 'b2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    /////////////////////////////////////////
    // Implementation of SequenceInterface //
    /////////////////////////////////////////

    public function testFront()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $expected = array('A' => 'A0', 'B' => 'B0');

        $this->assertTableElement($expected, $this->_collection->front());
    }

    public function testFrontWithEmptyCollection()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyCollectionException', 'Collection is empty.');
        $this->_collection->front();
    }

    public function testTryFront()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $expected = array('A' => 'A0', 'B' => 'B0');

        $element = null;
        $this->assertTrue($this->_collection->tryFront($element));
        $this->assertTableElement($expected, $element);
    }

    public function testTryFrontWithEmptyCollection()
    {
        $element = '<not null>';
        $this->assertFalse($this->_collection->tryFront($element));
        $this->assertSame('<not null>', $element); // Reference should not be changed on failure.
    }

    public function testBack()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $expected = array('A' => 'A1', 'B' => 'B1');

        $this->assertTableElement($expected, $this->_collection->back());
    }

    public function testBackWithEmptyCollection()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyCollectionException', 'Collection is empty.');
        $this->_collection->back();
    }

    public function testTryBack()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $expected = array('A' => 'A1', 'B' => 'B1');

        $element = null;
        $this->assertTrue($this->_collection->tryBack($element));
        $this->assertTableElement($expected, $element);
    }

    public function testTryBackWithEmptyCollection()
    {
        $element = '<not null>';
        $this->assertFalse($this->_collection->tryBack($element));
        $this->assertSame('<not null>', $element); // Reference should not be changed on failure.
    }

    public function testSorted()
    {
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $result = $this->_collection->sorted();

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    public function testSortedWithComparator()
    {
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $result = $this->_collection->sorted(
            function ($a, $b) {
                return iterator_to_array($b) > iterator_to_array($a);
            }
        );
        $expected = array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    public function testReversed()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->reversed();
        $expected = array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    public function testJoin()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $result = $this->_collection->join(array(
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result->elements());
    }

    ////////////////////////////////////////////////
    // Implementation of MutableSequenceInterface //
    ////////////////////////////////////////////////

    public function testSort()
    {
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->sort();
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSortWithComparator()
    {
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->sort(
            function ($a, $b) {
                return iterator_to_array($b) > iterator_to_array($a);
            }
        );
        $expected = array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSortWithEmptyCollection()
    {
        $this->_collection->sort();

        $this->assertSame(array(), $this->_collection->elements());
    }

    public function testSortWithSingleElement()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->sort();
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReverse()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->reverse();
        $expected = array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testAppend()
    {
        $this->_collection->append(
            array(
                array('A' => 'A0', 'B' => 'B0'),
                array('A' => 'A1', 'B' => 'B1')
            ),
            array(
                array('A' => 'A2', 'B' => 'B2'),
                array('A' => 'A3', 'B' => 'B3'),
            )
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testPushFront()
    {
        $this->_collection->pushFront(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushFront(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushFront(array('A' => 'A0', 'B' => 'B0'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testPopFront()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $element = $this->_collection->popFront();
        $expectedElement = array('A' => 'A0', 'B' => 'B0');
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElement($expectedElement, $element);
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testPopFrontWithEmptyCollection()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyCollectionException', 'Collection is empty.');
        $this->_collection->popFront();
    }

    public function testTryPopFront()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expectedElement = array('A' => 'A0', 'B' => 'B0');
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $element = null;
        $this->assertTrue($this->_collection->tryPopFront($element));
        $this->assertTableElement($expectedElement, $element);
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testTryPopFrontWithEmptyCollection()
    {
        $element = '<not null>';
        $this->assertFalse($this->_collection->tryPopFront($element));
        $this->assertSame('<not null>', $element); // Reference should not be changed on failure.
    }

    public function testPushBack()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testPopBack()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $element = $this->_collection->popBack();
        $expectedElement = array('A' => 'A2', 'B' => 'B2');
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElement($expectedElement, $element);
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testPopBackWithEmptyCollection()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\EmptyCollectionException', 'Collection is empty.');
        $this->_collection->popBack();
    }

    public function testTryPopBack()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expectedElement = array('A' => 'A2', 'B' => 'B2');
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $element = null;
        $this->assertTrue($this->_collection->tryPopBack($element));
        $this->assertTableElement($expectedElement, $element);
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testTryPopBackWithEmptyCollection()
    {
        $element = '<not null>';
        $this->assertFalse($this->_collection->tryPopBack($element));
        $this->assertSame('<not null>', $element); // Reference should not be changed on failure.
    }

    public function testResize()
    {
        $this->_collection->resize(3);
        $expected = array(
            array('A' => null, 'B' => null),
            array('A' => null, 'B' => null),
            array('A' => null, 'B' => null),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testResizeWithValue()
    {
        $this->_collection->resize(3, array('A' => 'A0', 'B' => 'B0'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testResizeToSmallerSize()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->resize(2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    //////////////////////////////////////////////
    // Implementation of RandomAccessInterface //
    /////////////////////////////////////////////

    public function testGet()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expected = array('A' => 'A1', 'B' => 'B1');

        $this->assertTableElement($expected, $this->_collection->get(1));
    }

    public function testGetWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $expected = array('A' => 'A2', 'B' => 'B2');

        $this->assertTableElement($expected, $this->_collection->get(-1));
    }

    public function testGetWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 0 is out of range.');
        $this->_collection->get(0);
    }

    public function testSlice()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(2);
        $expected = array(
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testSliceWithCount()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(1, 3);
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testSliceWithCountOverflow()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(2, 100);
        $expected = array(
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testSliceWithNegativeCount()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(1, -3);

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertSame(array(), $result->elements());
    }

    public function testSliceWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(-2);
        $expected = array(
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testSliceWithNegativeIndexAndCount()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->slice(-3, 2);
        $expected = array(
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testSliceWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->slice(1);
    }

    public function testRange()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->range(1, 3);
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testRangeWithNegativeIndices()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->range(-3, -1);
        $expected = array(
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertTableElements($expected, $result);
    }

    public function testRangeWithEndBeforeBegin()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $result = $this->_collection->range(3, 1);

        $this->assertInstanceOf(__NAMESPACE__ . '\Table', $result);
        $this->assertSame(array(), $result->elements());
    }

    public function testRangeWithInvalidBegin()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $result = $this->_collection->range(1, 3);
    }

    public function testRangeWithInvalidEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 100 is out of range.');
        $result = $this->_collection->range(1, 100);
    }

    public function testIndexOf()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));

        $this->assertSame(1, $this->_collection->indexOf(array('A' => 'A1', 'B' => 'B1')));
    }

    public function testIndexOfWithStartIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->assertSame(1, $this->_collection->indexOf(array('A' => 'A1', 'B' => 'B1'), 1));
        $this->assertSame(3, $this->_collection->indexOf(array('A' => 'A1', 'B' => 'B1'), 2));
    }

    public function testIndexOfWithAssociative()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $element = Phake::mock(__NAMESPACE__ . '\AssociativeInterface');
        Phake::when($element)
            ->elements(Phake::anyParameters())
            ->thenReturn(array(
                array('B', 'B1'),
                array('A', 'A1'),
            ))
        ;

        $this->assertSame(1, $this->_collection->indexOf($element));
    }

    public function testIndexOfWithTraversable()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $element = new ArrayIterator(array('A' => 'A1', 'B' => 'B1'));

        $this->assertSame(1, $this->_collection->indexOf($element));
    }

    public function testIndexOfWithNoMatch()
    {
        $this->assertNull($this->_collection->indexOf(array('A' => 'A1', 'B' => 'B1')));

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->assertNull($this->_collection->indexOf(array('A' => 'A1', 'B' => 'B1')));
    }

    public function testIndexOfWithAssociativeNoMatch()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $element = Phake::mock(__NAMESPACE__ . '\AssociativeInterface');
        Phake::when($element)
            ->elements(Phake::anyParameters())
            ->thenReturn(array(
                array('B', 'B1'),
                array('A', 'A1'),
                array('C', 'C1'),
            ))
        ;

        $this->assertNull($this->_collection->indexOf($element));
    }

    public function testIndexOfFailureInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            "Value of type 'string' cannot be compared to a table element."
        );
        $this->_collection->indexOf('A');
    }

    public function testIndexOfLast()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->assertSame(3, $this->_collection->indexOfLast(array('A' => 'A1', 'B' => 'B1')));
    }

    public function testIndexOfLastWithStartIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->assertSame(3, $this->_collection->indexOfLast(array('A' => 'A1', 'B' => 'B1'), 3));
        $this->assertSame(1, $this->_collection->indexOfLast(array('A' => 'A1', 'B' => 'B1'), 2));
    }

    public function testIndexOfLastWithNoMatch()
    {
        $this->assertNull($this->_collection->indexOfLast(array('A' => 'A1', 'B' => 'B1')));

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->assertNull($this->_collection->indexOfLast(array('A' => 'A1', 'B' => 'B1')));
    }

    public function testFind()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $comparator = function ($element) {
            return iterator_to_array($element) === array('A' => 'A1', 'B' => 'B1');
        };

        $this->assertSame(1, $this->_collection->find($comparator));
    }

    public function testFindLast()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $comparator = function ($element) {
            return iterator_to_array($element) === array('A' => 'A1', 'B' => 'B1');
        };

        $this->assertSame(3, $this->_collection->findLast($comparator));
    }

    ////////////////////////////////////////////////////
    // Implementation of MutableRandomAccessInterface //
    ////////////////////////////////////////////////////

    public function testSet()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->set(1, array('A' => 'A3', 'B' => 'B3'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSetWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->set(-2, array('A' => 'A3', 'B' => 'B3'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSetWithAssociative()
    {
        $element = Phake::mock(__NAMESPACE__ . '\AssociativeInterface');
        Phake::when($element)
            ->elements(Phake::anyParameters())
            ->thenReturn(array(
                array('B', 'B3'),
                array('A', 'A3'),
            ))
        ;
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->set(1, $element);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSetWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 0 is out of range.');
        $this->_collection->set(0, array('A' => 'A3', 'B' => 'B3'));
    }

    public function testSetWithInvalidAssociativeMissingKeys()
    {
        $element = Phake::mock(__NAMESPACE__ . '\AssociativeInterface');
        Phake::when($element)
            ->elements(Phake::anyParameters())
            ->thenReturn(array(
                array('A', 'A1'),
            ))
        ;
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->setExpectedException(
            'InvalidArgumentException',
            'Table element does not contain the required keys.'
        );
        $this->_collection->set(0, $element);
    }

    public function testSetWithInvalidAssociativeExtraKeys()
    {
        $element = Phake::mock(__NAMESPACE__ . '\AssociativeInterface');
        Phake::when($element)
            ->elements(Phake::anyParameters())
            ->thenReturn(array(
                array('B', 'B1'),
                array('A', 'A1'),
                array('C', 'C1'),
            ))
        ;
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\UnknownKeyException',
            'Key "C" does not exist.'
        );
        $this->_collection->set(0, $element);
    }

    public function testInsert()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->insert(1, array('A' => 'A1', 'B' => 'B1'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->insert(-1, array('A' => 'A1', 'B' => 'B1'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertAtEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->insert(2, array('A' => 'A2', 'B' => 'B2'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->insert(1, array('A' => 'A3', 'B' => 'B3'));
    }

    public function testInsertMany()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->insertMany(1, array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertManyAtEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->insertMany(2, array(
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertManyWithEmptyElements()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->insertMany(1, array());
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertManyWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->insertMany(-1, array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testInsertManyWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->insertMany(1, array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        ));
    }

    public function testRemove()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->remove(1);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->remove(-2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->remove(1);
    }

    public function testRemoveMany()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeMany(1);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveManyWithCount()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeMany(1, 2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveManyWithCountOverflow()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeMany(1, 100);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveManyWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeMany(-3, 2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveManyWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->removeMany(1, 2);
    }

    public function testRemoveRange()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $this->_collection->removeRange(1, 3);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveRangeToEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeRange(1, 3);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveRangeWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeRange(-3, -1);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveRangeWithEndBeforeBegin()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->removeRange(3, 1);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testRemoveRangeWithInvalidBegin()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->removeRange(1, 2);
    }

    public function testRemoveRangeWithInvalidEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 100 is out of range.');
        $this->_collection->removeRange(1, 100);
    }

    public function testReplace()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replace(1, array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceWithCount()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replace(
            1,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            ),
            2
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceWithCountLessThanSize()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replace(
            0,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            ),
            3
        );
        $expected = array(
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceWithCountOverflow()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replace(
            1,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            ),
            100
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceWithNegativeIndex()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replace(
            -3,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            ),
            2
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceWithInvalidIndex()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->replace(1, array());
    }

    public function testReplaceRange()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replaceRange(
            1,
            3,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            )
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceRangeWithNegativeIndices()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replaceRange(
            -3,
            -1,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            )
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceRangeWithZeroLength()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replaceRange(
            1,
            1,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            )
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceRangeWithEndBeforeBegin()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->replaceRange(
            1,
            0,
            array(
                array('A' => 'A4', 'B' => 'B4'),
                array('A' => 'A5', 'B' => 'B5'),
            )
        );
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A4', 'B' => 'B4'),
            array('A' => 'A5', 'B' => 'B5'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testReplaceRangeWithInvalidBegin()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->replaceRange(1, 2, array());
    }

    public function testReplaceRangeWithInvalidEnd()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 100 is out of range.');
        $this->_collection->replaceRange(1, 100, array());
    }

    public function testSwap()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->swap(1, 2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSwapWithNegativeIndices()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->swap(-1, -2);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testSwapWithInvalidIndex1()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 1 is out of range.');
        $this->_collection->swap(1, 2);
    }

    public function testSwapWithInvalidIndex2()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 100 is out of range.');
        $this->_collection->swap(1, 100);
    }

    public function testTrySwap()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
        );

        $this->assertTrue($this->_collection->trySwap(1, 2));
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testTrySwapWithNegativeIndices()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTrue($this->_collection->trySwap(-1, -2));
        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testTrySwapWithInvalidIndex1()
    {
        $this->assertFalse($this->_collection->trySwap(1, 2));
    }

    public function testTrySwapWithInvalidIndex2()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));

        $this->assertFalse($this->_collection->trySwap(1, 100));
    }

    /////////////////////////////////
    // Implementation of Countable //
    /////////////////////////////////

    public function testCount()
    {
        $this->assertSame(0, count($this->_collection));

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));

        $this->assertSame(3, count($this->_collection));

        $this->_collection->clear();

        $this->assertSame(0, count($this->_collection));
    }

    ////////////////////////////////
    // Implementation of Iterator //
    ////////////////////////////////

    public function testIteration()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        $this->_collection->pushBack(array('A' => 'A3', 'B' => 'B3'));
        $this->_collection->pushBack(array('A' => 'A4', 'B' => 'B4'));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
            array('A' => 'A3', 'B' => 'B3'),
            array('A' => 'A4', 'B' => 'B4'),
        );

        $this->assertTableElements($expected, iterator_to_array($this->_collection));
    }

    public function testIterationEmpty()
    {
        $this->assertTableElements(array(), iterator_to_array($this->_collection));
    }

    ///////////////////////////////////
    // Implementation of ArrayAccess //
    ///////////////////////////////////

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->_collection[0]));

        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->assertTrue(isset($this->_collection[0]));
    }

    public function testOffsetGet()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));

        $this->assertTableElement(array('A' => 'A0', 'B' => 'B0'), $this->_collection[0]);
    }

    public function testOffsetGetFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\IndexException', 'Index 0 is out of range.');

        $this->_collection[0];
    }

    public function testOffsetSet()
    {
        $this->_collection[] = array('A' => 'A0', 'B' => 'B0');
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());

        $this->_collection[0] = array('A' => 'A1', 'B' => 'B1');
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());

        $this->_collection[] = array('A' => 'A2', 'B' => 'B2');
        $expected = array(
            array('A' => 'A1', 'B' => 'B1'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testOffsetUnset()
    {
        $this->_collection->pushBack(array('A' => 'A0', 'B' => 'B0'));
        $this->_collection->pushBack(array('A' => 'A1', 'B' => 'B1'));
        $this->_collection->pushBack(array('A' => 'A2', 'B' => 'B2'));
        unset($this->_collection[1]);
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A2', 'B' => 'B2'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    ////////////////////////////
    // Model specific methods //
    ////////////////////////////

    public function testFromCollection()
    {
        $this->_collection = Table::fromCollection(array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testFromCollectionWithCollection()
    {
        $this->_collection = Table::fromCollection(new Vector(array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        )));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testFromCollectionWithIteratorElements()
    {
        $this->_collection = Table::fromCollection(array(
            new ArrayIterator(array('A' => 'A0', 'B' => 'B0')),
            new ArrayIterator(array('A' => 'A1', 'B' => 'B1')),
        ));
        $expected = array(
            array('A' => 'A0', 'B' => 'B0'),
            array('A' => 'A1', 'B' => 'B1'),
        );

        $this->assertTableElements($expected, $this->_collection->elements());
    }

    public function testFromCollectionEmptyFailure()
    {
        $this->setExpectedException(
            'LogicException',
            'Tables must have at least one column.'
        );
        Table::fromCollection(array());
    }
}
