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

        if ($form->isSubmitted() && $form->isValid()) {
            $value = $form->getData()['value'];

            $this->addFlash('success', $translator->trans('Value set to %value%', ['%value%' => $value]));
        }

        return $this->render('default/index.html.twig', [
          'form' => $form->createView(),
        ]);
    }
}
