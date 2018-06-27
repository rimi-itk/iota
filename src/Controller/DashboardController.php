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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard")
 *
 * Class DefaultController
 */
class DashboardController extends Controller
{
    /**
     * @Route("", name="dashboard")
     * @Method("GET")
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $form = $this->createSetDimmingLevel();
        $decodeMessageForm = $this->createDecodeMessageForm();

        return $this->render('dashboard/index.html.twig', [
            'form' => $form->createView(),
            'decode_message_form' => $decodeMessageForm->createView(),
        ]);
    }

    /**
     * @Route("set_dimming_level", name="set_dimming_level")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setDimmingLevel(Request $request, LoriotInterface $loriot)
    {
        $form = $this->createSetDimmingLevel();
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

    /**
     * @Route("decode_message", name="decode_message")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function decodeMessage(Request $request, LoriotInterface $loriot)
    {
        $form = $this->createDecodeMessageForm();
        $form->handleRequest($request);

        $message = $form->getData()['message'];
        $port = $form->getData()['port'];

        try {
            $response = $loriot->decodeMessage($message, $port);

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

    private function createSetDimmingLevel()
    {
        $form =
//            $this->createFormBuilder(null, ['attr' => ['id' => 'set_dimming_level']])
//            $this->get('form.factory')->createBuilder()
            $this->get('form.factory')->createNamedBuilder('set_dimming_level')
                ->setAction($this->generateUrl('set_dimming_level'))
                ->add('value', RangeType::class, [
                    'label' => 'Dimming level',
                    'required' => true,
                ])
                ->add('eui', TextType::class, [
                    'label' => 'Device EUI',
                    'required' => true,
                ])
                ->add('broadcast', SubmitType::class, [
                    'label' => 'Send',
                ])
                ->getForm()
        ;

        return $form;
    }

    private function createDecodeMessageForm()
    {
        $ports = [24, 25, 50, 51, 60, 99];

        $form = $this->createFormBuilder(null, ['attr' => ['id' => 'decode_message']])
            ->setAction($this->generateUrl('decode_message'))
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
