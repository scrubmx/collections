<?php
namespace Icecave\Collections;

use Eloquent\Liberator\Liberator;
use Icecave\Collections\Iterator\Traits;
use Phake;
use PHPUnit_Framework_TestCase;

class SetTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->collection = new Set();
        $this->incompatibleCollection = new Set(null, function () {});
    }

    private function verifyElements()
    {
        $arguments = func_get_args();

        if (end($arguments) instanceof Set) {
            $collection = array_pop($arguments);
        } else {
            $collection = $this->collection;
        }

        if (1 === count($arguments) && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        $this->assertSame(
            $arguments,
            $collection->elements()
        );
    }

    public function testConstructor()
    {
        $this->assertSame(0, $this->collection->size());
    }

    public function testConstructorWithArray()
    {
        $collection = new Set(array(1, 2, 3, 3, 4, 5));
        $this->verifyElements(1, 2, 3, 4, 5, $collection);
    }

    public function testClone()
    {
        $this->collection->addMany(array(1, 2, 3));

        $collection = clone $this->collection;
        $collection->remove(2);

        $this->verifyElements(1, 3, $collection);
        $this->verifyElements(1, 2, 3);
    }

    public function testCreate()
    {
        $collection = Set::create(
            1,
            2,
            3
        );

        $this->assertInstanceOf('Icecave\Collections\Set', $collection);
        $this->verifyElements(1, 2, 3, $collection);
    }

    public function testSerialization()
    {
        $collection = new Set(array(1, 2, 3));

        $packet = serialize($collection);
        $unserializedCollection = unserialize($packet);

        $this->assertSame(
            Liberator::liberate($unserializedCollection)->elements->elements(),
            Liberator::liberate($collection)->elements->elements()
        );
    }

    public function testSerializationOfComparator()
    {
        $collection = new Set(null, 'strcmp');

        $packet = serialize($collection);
        $collection = unserialize($packet);

        $this->assertSame('strcmp', Liberator::liberate($collection)->comparator);
    }

    public function testCanCompare()
    {
        $collection = new Set();

        $this->assertTrue($collection->canCompare(new Set()));
        $this->assertFalse($collection->canCompare(new Set(null, function () {})));
        $this->assertFalse($collection->canCompare(array()));
    }

    ///////////////////////////////////////////
    // Implementation of CollectionInterface //
    ///////////////////////////////////////////

    public function testSize()
    {
        $this->assertSame(0, $this->collection->size());

        $this->collection->addMany(array(1, 2, 3));
        $this->assertSame(3, $this->collection->size());

        $this->collection->clear();
        $this->assertSame(0, $this->collection->size());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->collection->isEmpty());

        $this->collection->add(1);
        $this->assertFalse($this->collection->isEmpty());

        $this->collection->clear();
        $this->assertTrue($this->collection->isEmpty());
    }

    public function testToString()
    {
        $this->assertSame('<Set 0>', $this->collection->__toString());

        $this->collection->addMany(array('a', 'b', 'c'));
        $this->assertSame('<Set 3 ["a", "b", "c"]>', $this->collection->__toString());

        $this->collection->add('d');
        $this->assertSame('<Set 4 ["a", "b", "c", ...]>', $this->collection->__toString());
    }

    //////////////////////////////////////////////////
    // Implementation of MutableCollectionInterface //
    //////////////////////////////////////////////////

    public function testClear()
    {
        $this->collection->add(1);
        $this->collection->clear();

        $this->assertTrue($this->collection->isEmpty());
    }

    //////////////////////////////////////////////
    // Implementation of IteratorTraitsProvider //
    //////////////////////////////////////////////

    public function testIteratorTraits()
    {
        $this->assertEquals(new Traits(true, true), $this->collection->iteratorTraits());
    }

    /////////////////////////////////////////
    // Implementation of IterableInterface //
    /////////////////////////////////////////

    public function testElements()
    {
        $this->assertSame(array(), $this->collection->elements());

        $this->collection->addMany(array(1, 2, 3));
        $this->assertSame(array(1, 2, 3), $this->collection->elements());
    }

    public function testContains()
    {
        $this->assertFalse($this->collection->contains(1));

        $this->collection->add(1);
        $this->assertTrue($this->collection->contains(1));
    }

    public function testFilter()
    {
        $this->collection->addMany(array(1, null, 3));

        $result = $this->collection->filter();
        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements(1, 3, $result);
    }

    public function testFilterWithPredicate()
    {
        $this->collection->addMany(array(1, 2, 3, 4, 5));

        $result = $this->collection->filter(
            function ($value) {
                return $value & 0x1;
            }
        );

        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements(1, 3, 5, $result);
    }

    public function testMap()
    {
        $this->collection->addMany(array(1, 2, 3));

        $result = $this->collection->map(
            function ($value) {
                return $value + 1;
            }
        );

        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements(2, 3, 4, $result);
    }

    public function testPartition()
    {
        $this->collection->addMany(array(1, 2, 3, 4, 5));

        $result = $this->collection->partition(
            function ($element) {
                return $element < 3;
            }
        );

        $this->assertTrue(is_array($result));
        $this->assertSame(2, count($result));

        list($left, $right) = $result;

        $this->assertInstanceOf('Icecave\Collections\Set', $left);
        $this->verifyElements(1, 2, $left);

        $this->assertInstanceOf('Icecave\Collections\Set', $right);
        $this->verifyElements(3, 4, 5, $right);
    }

    public function testEach()
    {
        $this->collection->addMany(array(1, 2, 3));

        $calls = array();
        $callback = function ($element) use (&$calls) {
            $calls[] = func_get_args();
        };

        $this->collection->each($callback);

        $expected = array(
            array(1),
            array(2),
            array(3),
        );

        $this->assertSame($expected, $calls);
    }

    public function testAll()
    {
        $this->collection->addMany(array(1, 2, 3));

        $this->assertTrue(
            $this->collection->all(
                function ($element) {
                    return is_int($element);
                }
            )
        );

        $this->assertFalse(
            $this->collection->all(
                function ($element) {
                    return $element > 2;
                }
            )
        );
    }

    public function testAny()
    {
        $this->collection->addMany(array(1, 2, 3));

        $this->assertTrue(
            $this->collection->any(
                function ($element) {
                    return $element > 2;
                }
            )
        );

        $this->assertFalse(
            $this->collection->any(
                function ($element) {
                    return is_float($element);
                }
            )
        );
    }

    ////////////////////////////////////////////////
    // Implementation of MutableIterableInterface //
    ////////////////////////////////////////////////

    public function testFilterInPlace()
    {
        $this->collection->addMany(array(1, null, 3));

        $this->collection->filterInPlace();

        $this->verifyElements(1, 3);
    }

    public function testFilterInPlaceWithPredicate()
    {
        $this->collection->addMany(array(1, 2, 3, 4, 5));

        $this->collection->filterInPlace(
            function ($value) {
                return $value & 0x1;
            }
        );

        $this->verifyElements(1, 3, 5);
    }

    public function testMapInPlace()
    {
        $this->collection->addMany(array(1, 2, 3));

        $this->collection->mapInPlace(
            function ($value) {
                return $value + 1;
            }
        );

        $this->verifyElements(2, 3, 4);
    }

    /////////////////////////////////
    // Implementation of Countable //
    /////////////////////////////////

    public function testCount()
    {
        $this->assertSame(0, count($this->collection));

        $this->collection->addMany(array(1, 2, 3));
        $this->assertSame(3, count($this->collection));

        $this->collection->clear();
        $this->assertSame(0, count($this->collection));
    }

    /////////////////////////////////////////
    // Implementation of IteratorAggregate //
    /////////////////////////////////////////

    public function testIteration()
    {
        $this->collection->addMany(array(1, 2, 3));

        $this->assertSame(
            array(1, 2, 3),
            iterator_to_array($this->collection)
        );
    }

    /**
     * @group regression
     * @link https://github.com/IcecaveStudios/collections/issues/60
     */
    public function testNestedIterator()
    {
        $input = array(1, 2, 3);
        $output = array();

        $this->collection->addMany($input);

        foreach ($this->collection as $e) {
            foreach ($this->collection as $element) {
                $output[] = $element;
            }
        }

        $this->assertSame(array(1, 2, 3, 1, 2, 3, 1, 2, 3), $output);
    }

    ////////////////////////////
    // Model specific methods //
    ////////////////////////////

    public function testCascade()
    {
        $this->collection->addMany(array(0, 2, 3));

        $this->assertSame(2, $this->collection->cascade(1, 2, 3));
    }

    public function testCascadeFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\UnknownKeyException', 'Key "c" does not exist.');

        $this->collection->cascade('a', 'b', 'c');
    }

    public function testCascadeWithDefault()
    {
        $this->assertSame(500, $this->collection->cascadeWithDefault(500, 1, 2, 3));

        $this->collection->addMany(array(0, 2, 3));

        $this->assertSame(2, $this->collection->cascadeWithDefault(500, 1, 2, 3));
    }

    public function testCascadeIterable()
    {
        $this->collection->addMany(array(0, 2, 3));

        $this->assertSame(2, $this->collection->cascadeIterable(array(1, 2, 3)));
    }

    public function testCascadeIterableWithDefault()
    {
        $this->assertSame(500, $this->collection->cascadeIterableWithDefault(500, array(1, 2, 3)));

        $this->collection->addMany(array(0, 2, 3));

        $this->assertSame(2, $this->collection->cascadeIterableWithDefault(500, array(1, 2, 3)));
    }

    public function testAdd()
    {
        $this->assertFalse($this->collection->contains(1));

        $this->assertTrue($this->collection->add(1));
        $this->assertTrue($this->collection->contains(1));

        $this->assertFalse($this->collection->add(1));
        $this->assertTrue($this->collection->contains(1));

        $this->verifyElements(1);
    }

    public function testAddIgnoresDuplicates()
    {
        $this->collection->add(1);
        $this->collection->add(1);

        $this->verifyElements(1);
    }

    public function testAddMaintainsSortOrder()
    {
        $this->collection->add(3);
        $this->collection->add(1);
        $this->collection->add(2);

        $this->verifyElements(1, 2, 3);
    }

    public function testAddMany()
    {
        $this->collection->addMany(array(1, 3, 1, 2));

        $this->verifyElements(1, 2, 3);
    }

    public function testRemove()
    {
        $this->assertFalse($this->collection->remove(1));

        $this->collection->add(1);
        $this->assertTrue($this->collection->remove(1));
        $this->assertFalse($this->collection->contains(1));
    }

    public function testRemoveMany()
    {
        $this->collection->addMany(array(1, 2, 3, 4, 5));

        $this->collection->removeMany(array(4, 2));

        $this->verifyElements(1, 3, 5);
    }

    public function testPop()
    {
        $source = Set::create(1, 2, 3, 4, 5);

        $this->collection->unionInPlace($source);

        $element = $this->collection->pop();

        $this->assertTrue($source->contains($element));
        $this->assertFalse($this->collection->contains($element));
    }

    public function testPopWithEmptySet()
    {
        $this->setExpectedException('Icecave\Collections\Exception\EmptyCollectionException');

        $this->collection->pop();
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsEqualSet($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isEqual, $this->collection->isEqualSet($set));
        $this->assertSame($isEqual, $set->isEqualSet($this->collection));
    }

    public function testIsEqualSetIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isEqualSet($set);
    }

    public function testIsEqualSetIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isEqualSet($this->incompatibleCollection);
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsSuperSet($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isSuperSet, $this->collection->isSuperSet($set));
    }

    public function testIsSuperSetIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isSuperSet($set);
    }

    public function testIsSuperSetIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isSuperSet($this->incompatibleCollection);
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsSubSet($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isSubSet, $this->collection->isSubSet($set));
    }

    public function testIsSubSetIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isSubSet($set);
    }

    public function testIsSubSetIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isSubSet($this->incompatibleCollection);
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsProperSuperSet($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isProperSuperSet, $this->collection->isProperSuperSet($set));
    }

    public function testIsProperSuperSetIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isProperSuperSet($set);
    }

    public function testIsProperSuperSetIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isProperSuperSet($this->incompatibleCollection);
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsProperSubSet($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isProperSubSet, $this->collection->isProperSubSet($set));
    }

    public function testIsProperSubSetIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isProperSubSet($set);
    }

    public function testIsProperSubSetIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isProperSubSet($this->incompatibleCollection);
    }

    /**
     * @dataProvider getMembershipSetData
     */
    public function testIsIntersecting($lhsElements, $rhsElements, $isEqual, $isSuperSet, $isSubSet, $isProperSuperSet, $isProperSubSet, $isIntersecting)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->assertSame($isIntersecting, $this->collection->isIntersecting($set));
        $this->assertSame($isIntersecting, $set->isIntersecting($this->collection));
    }

    public function testIsIntersectingIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->isIntersecting($set);
    }

    public function testIsIntersectingIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->isIntersecting($this->incompatibleCollection);
    }

    public function getMembershipSetData()
    {
        return array(
            // name                              lhs                rhs                is-equal is-superset is-subset is-proper-super is-proper-sub is-intersecting
            'empty'                     => array(array(),           array(),           true,    true,       true,     false,          false,        false),
            'empty lhs'                 => array(array(),           array(10, 20),     false,   false,      true,     false,          true,         false),
            'empty rhs'                 => array(array(10, 20),     array(),           false,   true,       false,    true,           false,        false),
            'non-intersecting less'     => array(array(10, 20),     array(30, 40),     false,   false,      false,    false,          false,        false),
            'non-intersecting greater'  => array(array(30, 40),     array(10, 20),     false,   false,      false,    false,          false,        false),
            'interleaved #1'            => array(array(10, 30, 50), array(20, 40),     false,   false,      false,    false,          false,        false),
            'interleaved #2'            => array(array(20, 40),     array(10, 30, 50), false,   false,      false,    false,          false,        false),
            'partial matches'           => array(array(10, 30, 50), array(30, 40),     false,   false,      false,    false,          false,        true),
            'equal set'                 => array(array(10, 20),     array(10, 20),     true,    true,       true,     false,          false,        true),
            'proper superset'           => array(array(10, 20, 30), array(10, 20),     false,   true,       false,    true,           false,        true),
            'proper subset'             => array(array(10, 20),     array(10, 20, 30), false,   false,      true,     false,          true,         true),
            'intersecting at tail/head' => array(array(10, 20),     array(20, 30),     false,   false,      false,    false,          false,        true),
            'intersecting at head/tail' => array(array(20, 30),     array(10, 20),     false,   false,      false,    false,          false,        true),
        );
    }

    /**
     * @dataProvider getUnionData
     */
    public function testUnion($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $result = $this->collection->union($set);
        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements($expectedElements, $result);
    }

    public function testUnionIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->union($set);
    }

    public function testUnionIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->union($this->incompatibleCollection);
    }

    /**
     * @dataProvider getUnionData
     */
    public function testUnionInPlace($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->collection->unionInPlace($set);
        $this->verifyElements($expectedElements);
    }

    public function testUnionInPlaceIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->unionInPlace($set);
    }

    public function testUnionInPlaceIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->unionInPlace($this->incompatibleCollection);
    }

    public function getUnionData()
    {
        return array(
            'empty'                     => array(array(),           array(),           array()),
            'empty lhs'                 => array(array(),           array(10, 20),     array(10, 20)),
            'empty rhs'                 => array(array(10, 20),     array(),           array(10, 20)),
            'non-intersecting less'     => array(array(10, 20),     array(30, 40),     array(10, 20, 30, 40)),
            'non-intersecting greater'  => array(array(30, 40),     array(10, 20),     array(10, 20, 30, 40)),
            'interleaved #1'            => array(array(10, 30, 50), array(20, 40),     array(10, 20, 30, 40, 50)),
            'interleaved #2'            => array(array(20, 40),     array(10, 30, 50), array(10, 20, 30, 40, 50)),
            'partial matches'           => array(array(10, 30, 50), array(30, 40),     array(10, 30, 40, 50)),
            'equal set'                 => array(array(10, 20),     array(10, 20),     array(10, 20)),
            'proper superset'           => array(array(10, 20, 30), array(10, 20),     array(10, 20, 30)),
            'proper subset'             => array(array(10, 20),     array(10, 20, 30), array(10, 20, 30)),
            'intersecting at tail/head' => array(array(10, 20),     array(20, 30),     array(10, 20, 30)),
            'intersecting at head/tail' => array(array(20, 30),     array(10, 20),     array(10, 20, 30)),
        );
    }

    /**
     * @dataProvider getIntersectData
     */
    public function testIntersect($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $result = $this->collection->intersect($set);
        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements($expectedElements, $result);
    }

    public function testIntersectIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->intersect($set);
    }

    public function testIntersectIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->intersect($this->incompatibleCollection);
    }

    /**
     * @dataProvider getIntersectData
     */
    public function testIntersectInPlace($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->collection->intersectInPlace($set);
        $this->verifyElements($expectedElements);
    }

    public function testIntersectInPlaceIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->intersectInPlace($set);
    }

    public function testIntersectInPlaceIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->intersectInPlace($this->incompatibleCollection);
    }

    public function getIntersectData()
    {
        return array(
            'empty'                     => array(array(),           array(),           array()),
            'empty lhs'                 => array(array(),           array(10, 20),     array()),
            'empty rhs'                 => array(array(10, 20),     array(),           array()),
            'non-intersecting less'     => array(array(10, 20),     array(30, 40),     array()),
            'non-intersecting greater'  => array(array(30, 40),     array(10, 20),     array()),
            'interleaved #1'            => array(array(10, 30, 50), array(20, 40),     array()),
            'interleaved #2'            => array(array(20, 40),     array(10, 30, 50), array()),
            'partial matches'           => array(array(10, 30, 50), array(30, 40),     array(30)),
            'equal set'                 => array(array(10, 20),     array(10, 20),     array(10, 20)),
            'proper superset'           => array(array(10, 20, 30), array(10, 20),     array(10, 20)),
            'proper subset'             => array(array(10, 20),     array(10, 20, 30), array(10, 20)),
            'intersecting at tail/head' => array(array(10, 20),     array(20, 30),     array(20)),
            'intersecting at head/tail' => array(array(20, 30),     array(10, 20),     array(20)),
        );
    }

    /**
     * @dataProvider getDiffData
     */
    public function testDiff($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $result = $this->collection->diff($set);
        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements($expectedElements, $result);
    }

    public function testDiffIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->diff($set);
    }

    public function testDiffIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->diff($this->incompatibleCollection);
    }

    /**
     * @dataProvider getDiffData
     */
    public function testDiffInPlace($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->collection->diffInPlace($set);
        $this->verifyElements($expectedElements);
    }

    public function testDiffInPlaceIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->diffInPlace($set);
    }

    public function testDiffInPlaceIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->diffInPlace($this->incompatibleCollection);
    }

    public function getDiffData()
    {
        return array(
            'empty'                     => array(array(),           array(),           array()),
            'empty lhs'                 => array(array(),           array(10, 20),     array()),
            'empty rhs'                 => array(array(10, 20),     array(),           array(10, 20)),
            'non-intersecting less'     => array(array(10, 20),     array(30, 40),     array(10, 20)),
            'non-intersecting greater'  => array(array(30, 40),     array(10, 20),     array(30, 40)),
            'interleaved #1'            => array(array(10, 30, 50), array(20, 40),     array(10, 30, 50)),
            'interleaved #2'            => array(array(20, 40),     array(10, 30, 50), array(20, 40)),
            'partial matches'           => array(array(10, 30, 50), array(30, 40),     array(10, 50)),
            'equal set'                 => array(array(10, 20),     array(10, 20),     array()),
            'proper superset'           => array(array(10, 20, 30), array(10, 20),     array(30)),
            'proper subset'             => array(array(10, 20),     array(10, 20, 30), array()),
            'intersecting at tail/head' => array(array(10, 20),     array(20, 30),     array(10)),
            'intersecting at head/tail' => array(array(20, 30),     array(10, 20),     array(30)),
        );
    }

    /**
     * @dataProvider getSymmetricDiffData
     */
    public function testSymmetricDiff($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $result = $this->collection->symmetricDiff($set);
        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->verifyElements($expectedElements, $result);
    }

    public function testSymmetricDiffIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->symmetricDiff($set);
    }

    public function testSymmetricDiffIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->symmetricDiff($this->incompatibleCollection);
    }

    /**
     * @dataProvider getSymmetricDiffData
     */
    public function testSymmetricDiffInPlace($lhsElements, $rhsElements, $expectedElements)
    {
        $this->collection->addMany($lhsElements);
        $set = new Set($rhsElements);

        $this->collection->symmetricDiffInPlace($set);
        $this->verifyElements($expectedElements);
    }

    public function testSymmetricDiffInPlaceIncompatibleType()
    {
        $set = Phake::mock(__NAMESPACE__ . '\SetInterface');
        $this->setExpectedException('InvalidArgumentException', 'The given set is not an instance of Icecave\Collections\Set.');
        $this->collection->symmetricDiffInPlace($set);
    }

    public function testSymmetricDiffInPlaceIncompatible()
    {
        $this->setExpectedException('InvalidArgumentException', 'The given set does not use the same ');
        $this->collection->symmetricDiffInPlace($this->incompatibleCollection);
    }

    public function getSymmetricDiffData()
    {
        return array(
            'empty'                     => array(array(),           array(),           array()),
            'empty lhs'                 => array(array(),           array(10, 20),     array(10, 20)),
            'empty rhs'                 => array(array(10, 20),     array(),           array(10, 20)),
            'non-intersecting less'     => array(array(10, 20),     array(30, 40),     array(10, 20, 30, 40)),
            'non-intersecting greater'  => array(array(30, 40),     array(10, 20),     array(10, 20, 30, 40)),
            'interleaved #1'            => array(array(10, 30, 50), array(20, 40),     array(10, 20, 30, 40, 50)),
            'interleaved #2'            => array(array(20, 40),     array(10, 30, 50), array(10, 20, 30, 40, 50)),
            'partial matches'           => array(array(10, 30, 50), array(30, 40),     array(10, 40, 50)),
            'equal set'                 => array(array(10, 20),     array(10, 20),     array()),
            'proper superset'           => array(array(10, 20, 30), array(10, 20),     array(30)),
            'proper subset'             => array(array(10, 20),     array(10, 20, 30), array(30)),
            'intersecting at tail/head' => array(array(10, 20),     array(20, 30),     array(10, 30)),
            'intersecting at head/tail' => array(array(20, 30),     array(10, 20),     array(10, 30)),
        );
    }

    ////////////////////////////////////////////////////////////////
    // Implementation of [Restricted|Extended]ComparableInterface //
    ////////////////////////////////////////////////////////////////

    /**
     * @dataProvider getCompareData
     */
    public function testCompare($lhs, $rhs, $expectedResult)
    {
        $lhs = new Set($lhs);
        $rhs = new Set($rhs);

        $cmp = $lhs->compare($rhs);

        if ($expectedResult < 0) {
            $this->assertLessThan(0, $cmp);
        } elseif ($expectedResult > 0) {
            $this->assertGreaterThan(0, $cmp);
        } else {
            $this->assertSame(0, $cmp);
        }

        $this->assertSame($expectedResult === 0, $lhs->isEqualTo($rhs));
        $this->assertSame($expectedResult === 0, $rhs->isEqualTo($lhs));

        $this->assertSame($expectedResult !== 0, $lhs->isNotEqualTo($rhs));
        $this->assertSame($expectedResult !== 0, $rhs->isNotEqualTo($lhs));

        $this->assertSame($expectedResult < 0, $lhs->isLessThan($rhs));
        $this->assertSame($expectedResult > 0, $rhs->isLessThan($lhs));

        $this->assertSame($expectedResult > 0, $lhs->isGreaterThan($rhs));
        $this->assertSame($expectedResult < 0, $rhs->isGreaterThan($lhs));

        $this->assertSame($expectedResult <= 0, $lhs->isLessThanOrEqualTo($rhs));
        $this->assertSame($expectedResult >= 0, $rhs->isLessThanOrEqualTo($lhs));

        $this->assertSame($expectedResult >= 0, $lhs->isGreaterThanOrEqualTo($rhs));
        $this->assertSame($expectedResult <= 0, $rhs->isGreaterThanOrEqualTo($lhs));
    }

    public function testCompareFailure()
    {
        $this->setExpectedException('Icecave\Parity\Exception\NotComparableException');
        $collection = new Set();
        $collection->compare(array());
    }

    public function getCompareData()
    {
        return array(
            'empty'         => array(array(),     array(),      0),
            'smaller'       => array(array(1),    array(1, 2), -1),
            'larger'        => array(array(1, 2), array(1),    +1),
            'same'          => array(array(1, 2), array(1, 2),  0),
            'lesser'        => array(array(1, 2), array(1, 3), -1),
            'greater'       => array(array(1, 2), array(1, 1), +1),
        );
    }
}
