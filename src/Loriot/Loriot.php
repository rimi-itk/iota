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

class Loriot extends AbstractLoriot
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        try {
            $client = new Client();
            $response = $client->post('https://iotnet.teracom.dk/1/rest', [
                RequestOptions::JSON => $message + [
                        'appid' => getenv('LORIOT_APP_ID'),
                    ],
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.getenv('LORIOT_API_KEY'),
                ],
                //RequestOptions::DEBUG => true,
            ]);

            return (string) $response->getBody();
        } catch (\Exception $e) {
            throw LoriotException::createFromException($e);
        }
    }
}
