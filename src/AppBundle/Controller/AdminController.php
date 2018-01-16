<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer;

class AdminController extends Controller
{
    /**
     * @Route("/admin/new-product", name="new-product")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function addNewProductAction(Request $request) {
        $form = $this->createFormBuilder()
            ->add('name', TextType::class, array('label' => 'Nazwa: '))
            ->add('price', MoneyType::class, array('label' => 'Cena: '))
            ->add('description', TextType::class, array(
                'label' => 'Opis produktu: ',
                'attr' => array('minlength' => 100)))
            ->add('save', SubmitType::class, array(
                'label' => 'Dodaj produkt',
                'attr' => array('class' => 'addProductButton')))
            ->add('button', ButtonType::class, array(
                'label' => 'Anuluj',
                'attr' => array('class' => 'cancelButton')))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $dateOfAddition = new \DateTime();

            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setDescription($data['description']);
            $product->setDateOfAddition($dateOfAddition);

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $message = (new \Swift_Message('Email'))
                ->setFrom('send@example.com')
                ->setTo('fake@example.com')
                ->setBody(
                    $this->renderView(
                        'email/email.html.twig',
                        array(
                            'name' => $data['name'],
                            'price' => $data['price'],
                            'description' => $data['description'],
                            'dateOfAddition' => $dateOfAddition,
                        )
                    )
                )
            ;

            $this->get('mailer')->send($message);

            return $this->redirectToRoute('list-of-products');

        }

        return $this->render('admin/new-product.html.twig', array(
            'product_form' => $form->createView(),
            'page_title' => 'Dodawanie nowego produktu'
        ));
    }

    /**
     * @Route("/admin/list", name="list-of-products")
     */
    public function listOfProductsAction(Request $request) {
        $repository = $this->getDoctrine()->getManager();
        $dql = "SELECT p FROM AppBundle:Product p ORDER BY p.dateOfAddition DESC, p.id DESC";
        $query = $repository->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $isGranted = false;

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $isGranted = true;
        }

        return $this->render('admin/list-of-products.html.twig', array(
            'page_title' => 'Lista produktów',
            'pagination' => $pagination,
            'isGranted' => $isGranted
        ));
    }
}
