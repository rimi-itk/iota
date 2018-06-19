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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="frontpage")
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        $form = $this->createFormBuilder()
          ->add('value', RangeType::class)
          ->add('broadcast', SubmitType::class, ['label' => 'Broadcast'])
          ->getForm();

        $form->handleRequest($request);

        $valueToBroadcast = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $valueToBroadcast = $form->getData()['value'];
        }

        return $this->render('default/index.html.twig', [
            'form' => $form->createView(),
            'value_to_broadcast' => $valueToBroadcast,
        ]);
    }
}
