<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use App\Loriot\LoriotInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/message/")
 *
 * Class DefaultController
 */
class MessageController extends Controller
{
    /**
     * @Route("", name="message")
     * @Method("GET")
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, LoriotInterface $loriot)
    {
        return $this->decode($request, $loriot);
    }

    /**
     * @Route("decode", name="message_decode")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function decode(Request $request, LoriotInterface $loriot)
    {
        $form = $this->createDecodeForm();
        $form->handleRequest($request);

        $result = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData()['message'];
            $port = $form->getData()['port'];

            try {
                $result = $loriot->decodeMessage($message, $port);
            } catch (\Throwable $exception) {
                $result = null !== $exception->getPrevious()
                    ? $exception->getPrevious()->getMessage()
                    : $exception->getMessage();
            }
        }

        return $this->render('message/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function createDecodeForm()
    {
        $ports = ['', 24, 25, 50, 51, 60, 99];

        $form = $this->createFormBuilder(null, ['attr' => ['id' => 'decode_message']])
            ->setMethod('GET')
            ->setAction($this->generateUrl('message_decode'))
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'help' => 'Hex message',
            ])
            ->add('port', ChoiceType::class, [
                'label' => 'Port',
                'choices' => array_combine($ports, $ports),
                'required' => true,
            ])
            ->add('decode', SubmitType::class, [
                'label' => 'Decode',
            ])
            ->getForm();

        return $form;
    }
}
