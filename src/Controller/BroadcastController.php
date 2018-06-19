<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/broadcast")
 *
 * Class DefaultController
 */
class BroadcastController extends Controller
{
    /**
     * @Route("", name="broadcast")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function broadcast(Request $request)
    {
        $value = $request->get('value');
        $response = new StreamedResponse(null, 200, ['content-type' => 'text/plain']);
        $response->setCallback(function () use ($value) {
            echo 'Broadcasting value: '.$value, PHP_EOL;
            ob_flush();
            flush();
            sleep(2);
            echo 'Hello World', PHP_EOL;
            ob_flush();
            flush();
            sleep(1);
            echo 'Done', PHP_EOL;
        });
        $response->send();

        return $response;
    }
}
