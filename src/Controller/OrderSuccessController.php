<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Classe\Mail;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderSuccessController extends AbstractController
{
    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_validate")
     */
    public function index($stripeSessionId, Cart $cart): Response
    {
        
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);
        
        // si on ne trouve pas de order on redirige vers la homepage
        if(!$order || $order->getUser() != $this->getUser()){
            return $this->redirectToRoute('home');
        }

        // Si le statut de la commande est égale à 0
        if ($order->getState() == 0 ){
            // vider la session "cart"
            $cart->remove();

            // si le paiement est accepté alors on passe l'attribut state à 1
            $order->setState(1);
            $this->entityManager->flush();

            // Envoyer un email à notre client pour lui confirmer sa commande
            $mail = new Mail();
            $content = "Bonjour ".$order->getUser()->getFirstName()."<br/>Merci pour votre commande.<br/>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec quis libero rutrum justo interdum posuere. Pellentesque ullamcorper eros sapien, in fringilla leo consectetur eu. Donec tincidunt turpis id consequat eleifend. Phasellus porttitor consequat nunc, id facilisis eros dictum et. Vestibulum a massa condimentum, semper dui in, faucibus dui. Sed eget lacinia ante. Maecenas ut diam mi. Maecenas suscipit ullamcorper tortor, sed aliquam purus elementum non.";
            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstName(), "Votre commande sur La Boutique Française est bien validée", $content );

        }
       
        // Afficher les quelques informations de la commande de l'utilisateur
       
        return $this->render('order_success/index.html.twig',[
            'order' => $order
        ]);
    }
}
