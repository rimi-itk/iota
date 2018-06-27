<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Loriot;

interface LoriotInterface
{
    /**
     * @param $eui
     *
     * @return bool
     */
    public function isValidEUI($eui);

    /**
     * @param $eui
     *
     * @throws \App\Loriot\Exception\InvalidEUIException
     */
    public function validateEUI($eui);

    public function sendMessage(array $message);

    public function setStatusReporting(int $interval, string $eui);

    /**
     * @param $dimmingLevel
     * @param $eui
     * @param int $port
     *
     * @throws \App\Loriot\Exception\InvalidEUIException
     *
     * @return string
     */
    public function sendDimmingLevel($dimmingLevel, string $eui);
}
