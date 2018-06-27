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

    public function sendDimmingLevel($dimmingLevel, $eui, $port = 60)
    {
        $this->validateEUI($eui);
        if (0 <= $dimmingLevel && $dimmingLevel <= 1) {
            $dimmingLevel = (int) (100 * $dimmingLevel);
        }
        if ($dimmingLevel < 0 || 100 < $dimmingLevel) {
            throw new \InvalidArgumentException('Invalid dimming level: '.$dimmingLevel);
        }

        $header = 0x01;
        $address = 0xFE;
        $bytes = pack('C*', $header, $address, $dimmingLevel);
        $data = bin2hex($bytes);

        $message = [
            'cmd' => 'tx',
            'EUI' => $eui,
            'port' => $port,
            'confirmed' => true,
            'data' => $data,
        ];

        return $this->sendMessage($message);
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
        $bytes = pack('H*', preg_replace('/\s+/', '', $message));

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

                    $value = ord($byte);
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

                    $value = ord($byte);
                    foreach ($days as $day => &$active) {
                        $active = 1 === ($value & 0x1) ? 'active' : 'not active';
                        $value >>= 1;
                    }

                    return $days;
                };

                // Is this correct?
                $offset = (0xFF === $bytes[0]) ? 1 : 0;
                $timestamp = 0;
                for ($i = 3 + $offset; $i >= 0 + $offset; --$i) {
                    $timestamp <<= 8;
                    $timestamp |= ord($bytes[$i]);
                }
                $time = new \DateTime();
                $time->setTimestamp($timestamp);

                $status = $getStatus($bytes[4 + $offset]);

                $rssi = ord($bytes[5 + $offset]);

                $stuff = substr($bytes, 6 + $offset, 5);
                $profile[0] = [
                    'id' => ord($stuff[0]),
                    'sequence' => ord($stuff[1]),
                    'address' => ord($stuff[2]),
                    'day' => $getDays($stuff[3]),
                    'current light dim level' => ord($stuff[4]),
                ];

                return [
                    'clock' => $time->format(\DateTime::ATOM),
                    'status' => $status,
                    'rssi' => $rssi,
                    'profile' => $profile,
                ];

            case 25:
            case 50:
            case 51:
            case 60:
            case 99:
            default:
                throw new \RuntimeException('Invalid port: '.$port);

            break;
        }
    }
}
