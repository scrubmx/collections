<?php
namespace Icecave\Collections\Exception;

use Exception;
use Icecave\Collections\TypeCheck\TypeCheck;
use Icecave\Repr\Repr;
use OutOfBoundsException;

/**
 * The key of an associative collection was not found in the set of existing keys.
 */
class UnknownKeyException extends OutOfBoundsException implements CollectionExceptionInterface
{
    /**
     * @param mixed          $key      The unknown key.
     * @param Exception|null $previous The previous exception, if any.
     */
    public function __construct($key, Exception $previous = null)
    {
        TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct('Key ' . Repr::repr($key) . ' does not exist.', 0, $previous);
    }
}
