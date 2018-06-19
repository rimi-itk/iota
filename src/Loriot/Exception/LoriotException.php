<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Loriot\Exception;

class LoriotException extends \Exception
{
    public static function createFromException(\Exception $exception)
    {
        return new static('', 0, $exception);
    }
}
