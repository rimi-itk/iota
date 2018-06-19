<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use App\Loriot\Exception\LoriotException;
use App\Loriot\LoriotInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @Method("GET")
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $form = $this->createBroadcastForm();

        return $this->render('broadcast/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("", name="broadcast_create")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function broadcast(Request $request, LoriotInterface $loriot)
    {
        $form = $this->createBroadcastForm();
        $form->handleRequest($request);

        $value = $form->getData()['value'];
        $eui = $form->getData()['eui'];

        try {
            $response = $loriot->sendDimmingLevel($value, $eui);

            return new JsonResponse($response);
        } catch (LoriotException $exception) {
            $content = null !== $exception->getPrevious()
                ? $exception->getPrevious()->getMessage()
                : $exception->getMessage();

            return new Response($content, 200, [
                'content-type' => 'text/plain',
            ]);
        }
    }

    private function createBroadcastForm()
    {
        $form = $this->createFormBuilder()
            ->add('value', RangeType::class, [
                'required' => true,
            ])
            ->add('eui', TextType::class, [
                'required' => true,
            ])
            ->add('broadcast', SubmitType::class, ['label' => 'Broadcast'])
            ->getForm();

        return $form;
    }
}
