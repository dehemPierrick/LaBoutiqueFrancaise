<?php
namespace App\Classe;
use App\Entity\Product;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{

    private $session;

    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->entityManager =$entityManager;
    }

    /* permet d'ajouter un produit dans le panier */
    public function add($id)
    {
        $cart = $this->session->get('cart', []); // on stock le panier
        
        if(!empty($cart[$id])){ // si dans le panier tu as bien un id de produit renseigné
            $cart[$id]++;
        }else{
            $cart[$id] = 1;
        }
        $this->session->set('cart',$cart);
    }

    /* permet d'afficher les données du panier */
    public function get()
    {
        return $this->session->get('cart');
    }

    /* permet de supprimer le panier au complet */
    public function remove()
    {
        return $this->session->remove('cart');
    }

    /* permet de supprimer le produit dans le panier */
    public function delete($id)
    {
        $cart = $this->session->get('cart', []); // on stock le panier
        unset($cart[$id]); //permet de retirer du tableau panier le produit qui a l'id communiqué dans l'url
        return $this->session->set('cart',$cart); // retourne le panier à jour
    }

    /* permet de supprimer une quantité d'un produit dans le panier */
    public function decrease($id)
    {
        $cart = $this->session->get('cart', []); // on stock le panier
        // vérifier si la quantité du produit dans le panier = 1
        if($cart[$id] >1 ){ // on supprime une quantité
            $cart[$id]--;
        }else{ //sinon on supprime le produit du panier
            unset($cart[$id]);
        }
        return $this->session->set('cart',$cart); // retourne le panier à jour
    }

    public function getAll(){
        $cartComplete = [];
        if($this->get()){
            foreach ($this->get() as $id => $quantity){
                $product_object = $this->entityManager->getRepository(Product::class)->findOneById($id);
                // si le produit n'existe pas dans la bdd 
                if(!$product_object){
                  $this->delete($id);
                  continue;
                }

                $cartComplete[] = [
                    'product' => $product_object,
                    'quantity' => $quantity
                ];
            }
        }

        return $cartComplete;
    }
}