<?php

namespace App\Controller;
use App\Classe\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $entityManager; // permet de se lier à l'ORM doctrine

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mon-panier", name="cart")
     */
    public function index(Cart $cart): Response
    {
        return $this->render('cart/index.html.twig',[
            'cart' => $cart->getAll() //renvoi le panier au complet
        ]);
    }

    /**
     * @Route("/cart/add/{id}", name="add_to_cart")
     */
    public function add(Cart  $cart, $id): Response
    {
        $cart->add($id);
        return $this->redirectToROute('cart') ;// redirection vers le panier
    }

    /**
     * @Route("/cart/remove", name="remove_my_cart")
     */
    public function remove(Cart  $cart): Response
    {
        $cart->remove();
        return $this->redirectToROute('products'); // redirection vers les produits
    }

    /**
     * @Route("/cart/delete/{id}", name="delete_to_cart")
     */
    public function delete(Cart  $cart, $id): Response
    {
        $cart->delete($id);
        return $this->redirectToROute('cart'); // redirection vers le panier
    }

    /**
     * @Route("/cart/decrease/{id}", name="decrease_to_cart")
     */
    public function decrease(Cart  $cart, $id): Response
    {
        $cart->decrease($id);
        return $this->redirectToROute('cart'); // redirection vers le panier
    }
}


