<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Loriot;

use App\Loriot\Exception\InvalidEUIException;

abstract class AbstractLoriot implements LoriotInterface
{
    public function isValidEUI($eui)
    {
        try {
            $this->validateEUI($eui);

            return true;
        } catch (InvalidEUIException $e) {
        }

        return false;
    }

    public function validateEUI($eui)
    {
        if (!preg_match('/^[0-9a-f]{16}$/i', $eui)) {
            throw new InvalidEUIException('Invalid EUI: '.$eui);
        }
    }
}
