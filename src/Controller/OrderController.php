<?php

namespace App\Controller;

use DateTime;
use App\Classe\Cart;
use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\OrderDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande", name="order")
     */
    public function index(Cart $cart, Request $request): Response
    {

        if(!$this->getUser()->getAddresses()->getValues()){ //on récupère les valeurs des adresses de l'uilisateur
            return $this->redirectToRoute('account_address_add') ; //si l'utilisateur n'a pas renseigné d'adresse on le redirige vers la page d'ajout d'adresse
        }
        $form =$this->createForm(OrderType::class, null, [
            'user' =>$this->getUser()
        ]);

        return $this->render('order/index.html.twig',[
            'form' => $form->createView(),
            'cart' => $cart->getAll(),
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap", methods={"POST"})
     */
    public function add(Cart $cart, Request $request): Response
    {

      
        $form =$this->createForm(OrderType::class, null, [
            'user' =>$this->getUser()
        ]);

        // on écoute a requête entrante du formulaire
        $form->handleRequest($request);

        // si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){
            $date = new DateTime();
            $carriers = $form->get('carriers')->getData(); // on récupère le nom du transporteur sélectionné
            $delivery = $form->get('addresses')->getData(); // on récupère l'adresse renseigné pour la livraison
   
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();
            if($delivery->getCompany()){ //si la société est renseigné
                $delivery_content .= '<br/>'.$delivery->getCompany(); 
            }
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getCountry();

            // enregistrer ma commande (entité order)
            $order = new Order();
            $reference = $date->format('dmY').'-'.uniqid(); // créer une référence à passer dans l'url pour la récupérer avec stripe
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($delivery_content);
            $order->setState(0); // commande non validée
            
            // on sauvegarde les données dans la table Order
            $this->entityManager->persist($order); // fige les datas
            
            // enregistrer mes produits (entité orderDetails)
            // récupération du panier
            foreach ($cart->getAll() as $product){
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
                $this->entityManager->persist($orderDetails); // fige les datas               
                            
            }

            $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
               

            return $this->render('order/add.html.twig',[
                'cart' => $cart->getAll(),
                'carrier' => $carriers,
                'delivery' => $delivery_content,
                'reference' => $order->getReference()
            ]);
            
        }
        return $this->redirectToRoute('cart'); // on redirige l'utilisateur vers le panier s'il entre une url sans passer par le post du formulaire       
    }
}
