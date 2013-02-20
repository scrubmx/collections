<?php
namespace Icecave\Collections;

use ArrayAccess;
use Countable;
use Icecave\Collections\TypeCheck\TypeCheck;
use Icecave\Repr\Repr;
use Iterator;
use LogicException;

class TableElement implements MutableAssociativeInterface, Countable, Iterator, ArrayAccess
{
    /**
     * @param Table                        $table
     * @param MutableRandomAccessInterface $elements
     * @param integer                      $index
     */
    public function __construct(
        Table $table,
        MutableRandomAccessInterface $elements,
        $index
    ) {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->table = $table;
        $this->elements = $elements;
        $this->index = $index * $table->elementSize();
    }

    ///////////////////////////////////////////
    // Implementation of CollectionInterface //
    ///////////////////////////////////////////

    /**
     * Fetch the number of elements in the collection.
     *
     * @see Map::isEmpty()
     *
     * @return integer The number of elements in the collection.
     */
    public function size()
    {
        $this->typeCheck->size(func_get_args());

        return $this->table()->elementSize();
    }

    /**
     * Check if the collection is empty.
     *
     * @return boolean True if the collection is empty; otherwise, false.
     */
    public function isEmpty()
    {
        $this->typeCheck->isEmpty(func_get_args());

        return false;
    }

    /**
     * Fetch a string representation of the collection.
     *
     * The string may not describe all elements of the collection, but should at least
     * provide information on the type and state of the collection.
     *
     * @return string A string representation of the collection.
     */
    public function __toString()
    {
        $elements = array();
        $index = 0;
        foreach ($this->elements() as $element) {
            if ($index++ === 3) {
                break;
            }

            list($key, $value) = $element;

            $elements[] = Repr::repr($key) . ' => ' . Repr::repr($value);
        }

        if ($this->size() > 3) {
            $format = '<TableElement %d [%s, ...]>';
        } else {
            $format = '<TableElement %d [%s]>';
        }

        return sprintf(
            $format,
            $this->size(),
            implode(', ', $elements)
        );
    }

    //////////////////////////////////////////////////
    // Implementation of MutableCollectionInterface //
    //////////////////////////////////////////////////

    /**
     * Remove all elements from the collection.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function clear()
    {
        $this->typeCheck->clear(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /////////////////////////////////////////
    // Implementation of IterableInterface //
    /////////////////////////////////////////

    /**
     * Fetch a native array containing the elements in the collection.
     *
     * @return array An array containing the elements in the collection.
     */
    public function elements()
    {
        $this->typeCheck->elements(func_get_args());

        $elements = array();
        foreach ($this->table()->columnNames() as $columnName) {
            $elements[] = array($columnName, $this->get($columnName));
        }

        return $elements;
    }

    /**
     * Check if the collection contains an element with the given value.
     *
     * @param mixed $value The value to check.
     *
     * @return boolean True if the collection contains $value; otherwise, false.
     */
    public function contains($value)
    {
        $this->typeCheck->contains(func_get_args());

        foreach ($this->keys() as $key) {
            if ($this->get($key) === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch a new collection with a subset of the elements from this collection.
     *
     * The predicate must be a callable with the following signature:
     *  function (mixed $key, mixed $value) { return $true_to_retain_element; }
     *
     * @param callable|null $predicate A predicate function used to determine which elements to include, or null to include all elements with non-null values.
     *
     * @return Map The filtered collection.
     */
    public function filtered($predicate = null)
    {
        $this->typeCheck->filtered(func_get_args());

        if (null === $predicate) {
            $predicate = function ($key, $value) {
                return null !== $value;
            };
        }

        $result = new Map;

        foreach ($this->keys() as $key) {
            $value = $this->get($key);
            if (call_user_func($predicate, $key, $value)) {
                $result->set($key, $value);
            }
        }

        return $result;
    }

    /**
     * Produce a new collection by applying a transformation to each element.
     *
     * The new elements produced by the transform need not be of the same type.
     * It is not guaranteed that the concrete type of the resulting collection will match this collection.
     *
     * The transform must be a callable with the following signature:
     *  function (mixed $key, mixed $value) { return array($new_key, $new_value); }
     *
     * @param callable $transform The transform to apply to each element.
     *
     * @return IterableInterface A new collection produced by applying $transform to each element in this collection.
     */
    public function map($transform)
    {
        $this->typeCheck->map(func_get_args());

        $result = new Map;

        foreach ($this->keys() as $key) {
            $element = call_user_func($transform, $key, $this->get($key));
            list($key, $value) = $element;
            $result->set($key, $value);
        }

        return $result;
    }

    ////////////////////////////////////////////////
    // Implementation of MutableIterableInterface //
    ////////////////////////////////////////////////

    /**
     * Filter this collection in-place.
     *
     * The predicate must be a callable with the following signature:
     *  function (mixed $key, mixed $value) { return $true_to_retain_element; }
     *
     * @param callable|null $predicate A predicate function used to determine which elements to retain, or null to retain all elements with non-null values.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function filter($predicate = null)
    {
        $this->typeCheck->filter(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Replace each element in the collection with the result of a transformation on that element.
     *
     * The new elements produced by the transform must be the same type.
     *
     * The transform must be a callable with the following signature:
     *  function (mixed $key, mixed $value) { return $new_value; }
     *
     * @param callable $transform The transform to apply to each element.
     */
    public function apply($transform)
    {
        $this->typeCheck->apply(func_get_args());

        foreach ($this->keys() as $key) {
            $this->set($key, call_user_func($transform, $key, $this->get($key)));
        }
    }

    ////////////////////////////////////////////
    // Implementation of AssociativeInterface //
    ////////////////////////////////////////////

    /**
     * Check if the collection contains an element with the given key.
     *
     * @param mixed $key The key to check.
     *
     * @return boolean True if the collection contains the given key; otherwise, false.
     */
    public function hasKey($key)
    {
        $this->typeCheck->hasKey(func_get_args());

        return in_array($key, $this->keys(), true);
    }

    /**
     * Fetch the value associated with the given key.
     *
     * @param mixed $key The key to fetch.
     *
     * @return mixed                         The associated value.
     * @throws Exception\UnknownKeyException if no such key exists.
     */
    public function get($key)
    {
        $this->typeCheck->get(func_get_args());

        $value = null;
        if ($this->tryGet($key, $value)) {
            return $value;
        }

        throw new Exception\UnknownKeyException($key);
    }

    /**
     * Fetch the value associated with the given key if it exists.
     *
     * @param mixed $key    The key to fetch.
     * @param mixed &$value Assigned the value associated with $key if it exists.
     *
     * @return boolean True if $key exists and $value was populated; otherwise, false.
     */
    public function tryGet($key, &$value)
    {
        $this->typeCheck->tryGet(func_get_args());

        $index = array_search($key, $this->keys(), true);
        if (false !== $index) {
            $value = $this->elements->get($this->index + $index);

            return true;
        }

        return false;
    }

    /**
     * Fetch the value associated with the given key, or a default value if it does not exist.
     *
     * @param mixed $key     The key to fetch.
     * @param mixed $default The default value to return if $key does not exist.
     *
     * @return mixed The value associated with $key, or the $default if nos such key exists.
     */
    public function getWithDefault($key, $default = null)
    {
        $this->typeCheck->getWithDefault(func_get_args());

        $value = null;
        if ($this->tryGet($key, $value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Return the value associated with the first key that exists.
     *
     * Takes a variable number of keys and searches for each one in order,
     * returns the value associated with the first key that exists.
     *
     * @param mixed $key            The key to search for.
     * @param mixed $additional,... Additional keys to search for.
     *
     * @return mixed                         The value associated with the first key that exists.
     * @throws Exception\UnknownKeyException if none of the keys exist.
     */
    public function cascade($key)
    {
        $this->typeCheck->cascade(func_get_args());

        return $this->cascadeIterable(func_get_args());
    }

    /**
     * Return the value associated with the first key that exists, or a default value if none of the provided keys exist.
     *
     * @param mixed $default        The default value to return if no such keys exist.
     * @param mixed $key            The key to search for.
     * @param mixed $additional,... Additional keys to search for.
     *
     * @return mixed The value associated with the first key that exists, or $default if none of the keys exist.
     */
    public function cascadeWithDefault($default, $key)
    {
        $this->typeCheck->cascadeWithDefault(func_get_args());

        $keys = func_get_args();
        $default = array_shift($keys);

        return $this->cascadeIterableWithDefault($default, $keys);
    }

    /**
     * Return the value associated with the first existing key in the given sequence.
     *
     * Behaves as per {@see Map::cascade()} except that the keys are provided as
     * a traversable (eg, array) instead of via a variable argument list.
     *
     * @param mixed<mixed> $keys The list of keys.
     *
     * @return mixed                         The value associated with the first key that exists.
     * @throws Exception\UnknownKeyException if none of the keys exist.
     */
    public function cascadeIterable($keys)
    {
        $this->typeCheck->cascadeIterable(func_get_args());

        $value = null;
        foreach ($keys as $key) {
            if ($this->tryGet($key, $value)) {
                return $value;
            }
        }

        throw new Exception\UnknownKeyException($key);
    }

    /**
     * Return the value associated with the first existing key in the given sequence, or a default value if none of the provided keys exist.
     *
     * Behaves as per {@see Map::cascadeDefault()} except that the keys are provided as
     * a traversable (eg, array) instead of via a variable argument list.
     *
     * @param mixed        $default The default value to return if no such keys exist.
     * @param mixed<mixed> $keys    The list of keys.
     *
     * @return mixed The value associated with the first key that exists, or $default if none of the keys exist.
     */
    public function cascadeIterableWithDefault($default, $keys)
    {
        $this->typeCheck->cascadeIterableWithDefault(func_get_args());

        $value = null;
        foreach ($keys as $key) {
            if ($this->tryGet($key, $value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Fetch a native array containing the keys in the collection.
     *
     * There is no guarantee that the order of keys will match the order of values produced by {@see Map::values()}.
     *
     * @return array A native array containing the keys in the collection.
     */
    public function keys()
    {
        $this->typeCheck->keys(func_get_args());

        return $this->table()->columnNames();
    }

    /**
     * Fetch a native array containing the values in the collection.
     *
     * There is no guarantee that the order of values will match the order of keys produced by {@see Map::keys()}.
     *
     * @return array A native array containing the values in the collection.
     */
    public function values()
    {
        $this->typeCheck->values(func_get_args());

        $values = array();
        foreach ($this->keys() as $key) {
            $values[] = $this->get($key);
        }

        return $values;
    }

    /**
     * Produce a new collection containing the elements of this collection and one or more other collections.
     *
     * Any existing keys are overwritten from left to right.
     * It is not guaranteed that the concrete type of the merged collection will match this collection.
     *
     * @param AssociativeInterface $collection     The collection to combine.
     * @param AssociativeInterface $additional,... Additional collections to combine.
     *
     * @return AssociativeInterface The merged collection.
     */
    public function combine(AssociativeInterface $collection)
    {
        $this->typeCheck->combine(func_get_args());

        $result = new Map($this);

        foreach (func_get_args() as $collection) {
            foreach ($collection->elements() as $element) {
                list($key, $value) = $element;
                $result->set($key, $value);
            }
        }

        return $result;
    }

    /**
     * Create a new collection containing the elements associated with the provided keys.
     *
     * It is not guaranteed that the concrete type of the projected collection will match this collection.
     *
     * @param mixed $key            The key to include in the new collection.
     * @param mixed $additional,... Ådditional keys to include in the new collection.
     *
     * @return AssociativeInterface The projection of the collection.
     */
    public function project($key)
    {
        $this->typeCheck->project(func_get_args());

        return $this->projectIterable(func_get_args());
    }

    /**
     * Create a new collection containing the elements associated with the provided keys.
     *
     * It is not guaranteed that the concrete type of the projected collection will match this collection.
     *
     * @param mixed<mixed> $keys The keys to include in the new collection.
     *
     * @return AssociativeInterface The projection of the collection.
     */
    public function projectIterable($keys)
    {
        $this->typeCheck->projectIterable(func_get_args());

        $result = new Map;

        $value = null;
        foreach ($keys as $key) {
            if ($this->tryGet($key, $value)) {
                $result->set($key, $value);
            }
        }

        return $result;
    }

    ///////////////////////////////////////////////////
    // Implementation of MutableAssociativeInterface //
    ///////////////////////////////////////////////////

    /**
     * Associate a value with a key.
     *
     * Associates $value with $key regardless of whether or not $key already exists.
     *
     * @see Map::add()
     * @see Map::replace()
     *
     * @param mixed $key   The element's key.
     * @param mixed $value The element's value.
     */
    public function set($key, $value)
    {
        $this->typeCheck->set(func_get_args());

        $this->replace($key, $value);
    }

    /**
     * Associate a value with a new key.
     *
     * Associates $value with $key only if $key does not already exist.
     *
     * @see Map::set()
     * @see Map::replace()
     * @see Map::tryAdd()
     *
     * @param mixed $key   The element's key.
     * @param mixed $value The element's value.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function add($key, $value)
    {
        $this->typeCheck->add(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Associate a value with a new key.
     *
     * Associates $value with $key only if $key does not already exist.
     *
     * @see Map::add()
     *
     * @param mixed $key   The element's key.
     * @param mixed $value The element's value.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function tryAdd($key, $value)
    {
        $this->typeCheck->tryAdd(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Associate a new value with an existing key.
     *
     * Associates $value with $key only if $key already exists.
     *
     * @see Map::add()
     * @see Map::set()
     * @see Map::tryReplace()
     *
     * @param mixed $key   The element's key.
     * @param mixed $value The element's value.
     *
     * @return mixed                         The value previously associated with this key.
     * @throws Exception\UnknownKeyException if $key does not already exist.
     */
    public function replace($key, $value)
    {
        $this->typeCheck->replace(func_get_args());

        $previous = null;
        if (!$this->tryReplace($key, $value, $previous)) {
            throw new Exception\UnknownKeyException($key);
        }

        return $previous;
    }

    /**
     * Associate a new value with an existing key.
     *
     * Associates $value with $key only if $key already exists.
     *
     * @see Map::replace()
     *
     * @param mixed $key       The element's key.
     * @param mixed $value     The element's value.
     * @param mixed &$previous Assigned the value previously associated with $key.
     *
     * @return boolean True if $key already exists and the new value has been set; otherwise, false.
     */
    public function tryReplace($key, $value, &$previous = null)
    {
        $this->typeCheck->tryReplace(func_get_args());

        $index = array_search($key, $this->keys(), true);
        if (false === $index) {
            return false;
        }

        $internalIndex = $this->index + $index;
        $previous = $this->elements->get($internalIndex);
        $this->elements->set($internalIndex, $value);

        return true;
    }

    /**
     * Remove an element from the collection.
     *
     * @param mixed $key The key of the element to remove.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function remove($key)
    {
        $this->typeCheck->remove(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Remove an element from the collection.
     *
     * @param mixed $key    The key of the element to remove.
     * @param mixed &$value Assigned the value associated with $key if it exists.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function tryRemove($key, &$value = null)
    {
        $this->typeCheck->tryRemove(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Add the elements from one or more other collections to this collection.
     *
     * Any existing keys are overwritten from left to right.
     *
     * @param AssociativeInterface $collection     The collection to merge.
     * @param AssociativeInterface $additional,... Additional collections to merge.
     */
    public function merge(AssociativeInterface $collection)
    {
        $this->typeCheck->merge(func_get_args());

        foreach (func_get_args() as $collection) {
            foreach ($collection->elements() as $element) {
                list($key, $value) = $element;
                $this->set($key, $value);
            }
        }
    }

    /**
     * Swap the elements associated with two keys.
     *
     * @param mixed $key1 The key of the first element.
     * @param mixed $key2 The key of the second element.
     *
     * @throws Exception\UnknownKeyException if $key1 or $key2 does not already exist.
     */
    public function swap($key1, $key2)
    {
        $this->typeCheck->swap(func_get_args());

        $temp = $this->get($key1);
        $this->replace($key1, $this->get($key2));
        $this->replace($key2, $temp);
    }

    /**
     * Swap the elements associated with two keys.
     *
     * @param mixed $key1 The key of the first element.
     * @param mixed $key2 The key of the second element.
     *
     * @return boolean True if $key1 and $key2 exist and the swap is successful.
     */
    public function trySwap($key1, $key2)
    {
        $this->typeCheck->trySwap(func_get_args());

        if (
            !in_array($key1, $this->keys(), true) ||
            !in_array($key2, $this->keys(), true)
        ) {
            return false;
        }

        $this->swap($key1, $key2);

        return true;
    }

    /**
     * Move an element from one key to another, replacing the target key if it already exists.
     *
     * @see Map::tryMove()
     * @see Map::rename()
     * @see Map::tryRename()
     *
     * @param mixed $source The existing key.
     * @param mixed $target The new key.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function move($source, $target)
    {
        $this->typeCheck->move(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Move an element from one key to another, replacing the target key if it already exists.
     *
     * @see Map::move()
     * @see Map::rename()
     * @see Map::tryRename()
     *
     * @param mixed $source The existing key.
     * @param mixed $target The new key.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function tryMove($source, $target)
    {
        $this->typeCheck->tryMove(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Move an element from one key to another.
     *
     * It is an error if the target key already exists.
     *
     * @see Map::move()
     * @see Map::tryMove()
     * @see Map::tryRename()
     *
     * @param mixed $source The existing key.
     * @param mixed $target The new key.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function rename($source, $target)
    {
        $this->typeCheck->rename(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /**
     * Move an element from one key to another.
     *
     * It is an error if the target key already exists.
     *
     * @see Map::move()
     * @see Map::tryMove()
     * @see Map::rename()
     *
     * @param mixed $source The existing key.
     * @param mixed $target The new key.
     *
     * @throws LogicException as this operation is not supported by table elements.
     */
    public function tryRename($source, $target)
    {
        $this->typeCheck->tryRename(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    /////////////////////////////////
    // Implementation of Countable //
    /////////////////////////////////

    public function count()
    {
        $this->typeCheck->count(func_get_args());

        return $this->size();
    }

    ////////////////////////////////
    // Implementation of Iterator //
    ////////////////////////////////

    /**
     * @return mixed
     */
    public function current()
    {
        $this->typeCheck->current(func_get_args());

        if (null === $this->currentIndex) {
            return null;
        }

        return $this->elements->get($this->index + $this->currentIndex);
    }

    /**
     * @return string|null
     */
    public function key()
    {
        $this->typeCheck->key(func_get_args());

        if (null === $this->currentIndex) {
            return null;
        }

        $keys = $this->keys();

        return $keys[$this->currentIndex];
    }

    public function next()
    {
        $this->typeCheck->next(func_get_args());

        if (++$this->currentIndex >= $this->size()) {
            $this->currentIndex = null;
        }
    }

    public function rewind()
    {
        $this->typeCheck->rewind(func_get_args());

        $this->currentIndex = 0;
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        $this->typeCheck->valid(func_get_args());

        return null !== $this->currentIndex;
    }

    ///////////////////////////////////
    // Implementation of ArrayAccess //
    ///////////////////////////////////

    /**
     * Check if the collection contains an element with the given key.
     *
     * @param mixed $offset The key to check.
     *
     * @return boolean True if the collection contains the given key; otherwise, false.
     */
    public function offsetExists($offset)
    {
        $this->typeCheck->offsetExists(func_get_args());

        return $this->hasKey($offset);
    }

    /**
     * Fetch the value associated with the given key.
     *
     * @param mixed $offset The key to fetch.
     *
     * @return mixed                         The associated value.
     * @throws Exception\UnknownKeyException if no such key exists.
     */
    public function offsetGet($offset)
    {
        $this->typeCheck->offsetGet(func_get_args());

        return $this->get($offset);
    }

    /**
     * Associate a value with a key.
     *
     * Associates $value with $offset regardless of whether or not $key already exists.
     *
     * @param mixed $offset The element's key.
     * @param mixed $value  The element's value.
     */
    public function offsetSet($offset, $value)
    {
        $this->typeCheck->offsetSet(func_get_args());

        $this->replace($offset, $value);
    }

    /**
     * Remove an element from the collection, if it is present.
     *
     * @param mixed $offset The key of the element to remove.
     */
    public function offsetUnset($offset)
    {
        $this->typeCheck->offsetUnset(func_get_args());

        throw new LogicException(sprintf(
            'Table elements do not support %s().',
            __FUNCTION__
        ));
    }

    ////////////////////////////
    // Model specific methods //
    ////////////////////////////

    /**
     * @return Table
     */
    public function table()
    {
        $this->typeCheck->table(func_get_args());

        return $this->table;
    }

    private $typeCheck;
    private $table;
    private $elements;
    private $index;
    private $currentIndex;
}
