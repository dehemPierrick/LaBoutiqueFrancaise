<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Address;
use App\Form\AddressType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountAddressController extends AbstractController
{
    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/compte/adresses", name="account_address")
     */
    public function index(): Response
    {
        return $this->render('account/address.html.twig');
    }

    /**
     * @Route("/compte/ajouter-une-adresse", name="account_address_add")
     */
    public function add(Cart $cart, Request $request): Response
    {
        //instancie la classe Address
        $address = new Address();

        //instancie le formulaire
        $form = $this->createForm(AddressType::class, $address );
        
        // on écoute a requête entrante du formulaire
        $form->handleRequest($request);
        
        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){

            //on récupère l'utilisateur connecté
            $address->setUser($this->getUser());
            
            // on sauvegarde les données dans la table User
            $this->entityManager->persist($address); // fige les datas
            $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
            
            // si j'ai des produits dans mon panier je me redirige vers le tunel d'achat
            if($cart->get()){
                return $this->redirectToRoute('order'); 
            }else{
                return $this->redirectToRoute('account_address'); // on redirige l'utiisateur vers la vue générale du compte
            }
         
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/compte/modifier-une-adresse/{id}", name="account_address_edit")
     */
    public function edit(Request $request, $id): Response
    {
        // récupération de l'id de l'adresse associé au compte pour la modification
        $address = $this->entityManager->getRepository(Address::class)->findOneById($id);

        // si l'adresse existe ou si mon adresse est bien une adresse associé au compte qui est connecté
        if (!$address || $address->getUser() != $this->getUser()){
            return $this->redirectToRoute('account_address');
        }
        
        //instancie le formulaire
        $form = $this->createForm(AddressType::class, $address );
        
        // on écoute a requête entrante du formulaire
        $form->handleRequest($request);
        
        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){
            $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
            return $this->redirectToRoute('account_address'); // on redirige l'utiisateur vers la vue générale du compte
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/compte/supprimer-une-adresse/{id}", name="account_address_delete")
     */
    public function delete($id): Response
    {
        // récupération de l'id de l'adresse associé au compte pour la modification
        $address = $this->entityManager->getRepository(Address::class)->findOneById($id);

        // si l'adresse existe et si mon adresse est bien une adresse associé au compte qui est connecté
        if ($address && $address->getUser() == $this->getUser()){
            $this->entityManager->remove($address); // suppression de l'adresse 
            $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
        }

        return $this->redirectToRoute('account_address'); // on redirige l'utiisateur vers la vue générale du compte
    }
}
