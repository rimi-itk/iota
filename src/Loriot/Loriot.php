<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Loriot;

use App\Entity\Item;
use App\Loriot\Exception\LoriotException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Loriot extends AbstractLoriot
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ParameterBagInterface */
    private $parameters;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameters)
    {
        $this->entityManager = $entityManager;
        $this->parameters = $parameters;
    }

    public function sendDimmingLevel($dimmingLevel, string $eui)
    {
        $port = 60;
        $this->validateEUI($eui);
        if (0 <= $dimmingLevel && $dimmingLevel <= 1) {
            $dimmingLevel = (int) (100 * $dimmingLevel);
        }
        if ($dimmingLevel < 0 || 100 < $dimmingLevel) {
            throw new \InvalidArgumentException('Invalid dimming level: '.$dimmingLevel);
        }

        $header = 0x01;
        $address = 0xFE;
        $bytes = [$header, $address, $dimmingLevel];

        $message = [
            'cmd' => 'tx',
            'EUI' => $eui,
            'port' => $port,
            'confirmed' => true,
            'data' => $this->toHex($bytes),
        ];

        return $this->sendMessage($message);
    }

    public function setStatusReporting(int $interval, string $eui)
    {
        $port = 50;
        $this->validateEUI($eui);

        $header = 0x07;
        $payload = [
            ($interval >> 000) & 0xFF,
            ($interval >> 010) & 0xFF,
            ($interval >> 020) & 0xFF,
            ($interval >> 030) & 0xFF,
        ];

        $bytes = array_merge([$header], $payload);

        $message = [
            'cmd' => 'tx',
            'EUI' => $eui,
            'port' => $port,
            'confirmed' => true,
            'data' => $this->toHex($bytes),
        ];

        $this->sendMessage($message);
    }

    public function sendMessage(array $message)
    {
        $item = new Item(__METHOD__);
        $item->setData(['message' => $message]);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        $config = $this->parameters->get('loriot');

        try {
            $client = new Client();
            $response = $client->post($config['api_url'], [
                RequestOptions::JSON => $message + [
                        'appid' => $config['app_id'],
                    ],
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$config['api_key'],
                ],
                //RequestOptions::DEBUG => true,
            ]);

            return json_decode((string) $response->getBody());
        } catch (\Exception $e) {
            throw LoriotException::createFromException($e);
        }
    }

    public function decodeMessage(string $message, int $port)
    {
        $bytes = array_map('ord', str_split(pack('H*', preg_replace('/\s+/', '', $message))));

        switch ($port) {
            case 24:
                $getStatus = function ($byte) {
                    $statuses = [
                        'DALI error' => false,
                        'DALI Connection error' => false,
                        'LDR error' => false,
                        'THR error' => false,
                        'DIG error' => false,
                        'HW error' => false,
                        'FW error' => false,
                        'Relay 2' => false,
                    ];

                    $value = $byte;
                    foreach ($statuses as $status => &$active) {
                        $active = 1 === ($value & 0x1);
                        $value >>= 1;
                    }

                    return $statuses;
                };

                $getDays = function ($byte) {
                    $days = [
                        'Holidays' => false,
                        'Monday' => false,
                        'Tuesday' => false,
                        'Wednesday' => false,
                        'Thursday' => false,
                        'Friday' => false,
                        'Saturday' => false,
                        'Sunday' => false,
                    ];

                    $value = $byte;
                    foreach ($days as $day => &$active) {
                        $active = 1 === ($value & 0x1) ? 'active' : 'not active';
                        $value >>= 1;
                    }

                    return $days;
                };

                // Is this correct?
                $offset = (0xFF === $bytes[0]) ? 1 : 0;
                $time = $this->getTime(array_slice($bytes, $offset, 4));
                $status = $getStatus($bytes[4 + $offset]);
                $rssi = $bytes[5 + $offset];

                $stuff = array_slice($bytes, 6 + $offset, 5);
                $profile[0] = [
                    'id' => $stuff[0],
                    'sequence' => $stuff[1],
                    'address' => $stuff[2],
                    'day' => $getDays($stuff[3]),
                    'current light dim level' => $stuff[4],
                ];

                return [
                    'clock' => $time->format(\DateTime::ATOM),
                    'status' => $status,
                    'rssi' => $rssi,
                    'profile' => $profile,
                ];

            case 99:
                switch ($bytes[0]) {
                    case 0x00:
                        return [
                            'header' => 'boot',
                            'serial' => $this->formatHex(array_slice($bytes, 1, 4)),
                            'firmware' => [
                                'major' => $this->formatDecimal(array_slice($bytes, 5, 1)),
                                'minor' => $this->formatDecimal(array_slice($bytes, 6, 1)),
                                'patch' => $this->formatDecimal(array_slice($bytes, 7, 1)),
                            ],
                            'clock' => $this->getTime(array_slice($bytes, 8, 4)),
                            'hardware' => [
                                'hw' => $bytes[12],
                                'opt' => $bytes[13],
                            ],
                        ];

                    case 0x01:
                        return [
                            'header' => 'shutdown',
                        ];

                    case 0x10:
                        return [
                            'header' => 'error code',
                            'error' => $this->formatHex($bytes[1]),
                        ];
                }

                throw new \RuntimeException('Invalid message: '.$this->formatHex($bytes));
            case 25:
            case 50:
            case 51:
            case 60:
            default:
                throw new \RuntimeException('Invalid port: '.$port);

            break;
        }
    }

    private function toHex(array $bytes)
    {
        return bin2hex(pack('C*', ...$bytes));
    }

    private function formatHex($bytes, $flip = false)
    {
        if (!is_array($bytes)) {
            $bytes = [$bytes];
        }
        if ($flip) {
            $bytes = array_reverse($bytes);
        }

        return implode(' ', array_map(function ($byte) {
            return sprintf('%02X', $byte);
        }, $bytes));
    }

    private function formatDecimal($bytes, $flip = false)
    {
        if (!is_array($bytes)) {
            $bytes = [$bytes];
        }
        if ($flip) {
            $bytes = array_reverse($bytes);
        }

        return implode(' ', array_map(function ($byte) {
            return sprintf('%d', $byte);
        }, $bytes));
    }

    private function getTime(array $bytes)
    {
        $timestamp = 0;
        for ($i = count($bytes) - 1; $i >= 0; --$i) {
            $timestamp <<= 8;
            $timestamp |= $bytes[$i];
        }
        $time = new \DateTime();
        $time->setTimestamp($timestamp);

        return $time;
    }
}
