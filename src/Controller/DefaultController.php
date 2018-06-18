<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultController extends Controller
{
    public function index(Request $request, TranslatorInterface $translator)
    {
        $form = $this->createFormBuilder()
          ->add('value', RangeType::class)
          ->add('update', SubmitType::class, ['label' => 'Update'])
          ->getForm();

        $form->handleRequest($request);

        $valueToBroadcast = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $valueToBroadcast = $form->getData()['value'];

            $this->addFlash('success', $translator->trans('Value set to %value%', ['%value%' => $valueToBroadcast]));
        }

        return $this->render('default/index.html.twig', [
            'form' => $form->createView(),
            'value_to_broadcast' => $valueToBroadcast,
        ]);
    }

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
