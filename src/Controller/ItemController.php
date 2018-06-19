<?php

/*
 * This file is part of Lygtepæl.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/item")
 *
 * Class DefaultController
 */
class ItemController extends Controller
{
    /**
     * @Route("", name="item_index")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(ItemRepository $itemRepository)
    {
        $items = $itemRepository->findBy([], ['createdAt' => Criteria::DESC]);

        return $this->render('item/index.html.twig', [
            'items' => $items,
        ]);
    }

    /**
     * @Route("", name="item_create")
     * @Method("POST")
     *
     * @param Request $request
     */
    public function create(Request $request, EntityManagerInterface $entityManager)
    {
        $data = json_decode($request->getContent());
        if (null === $data) {
            throw new BadRequestHttpException('Data must be a non-null json object');
        }
        $item = new Item();
        $item
            ->setCreatedAt(new \DateTime())
            ->setData($data);
        $entityManager->persist($item);
        $entityManager->flush();

        return new JsonResponse($data);
    }
}
