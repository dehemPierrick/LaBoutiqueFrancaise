<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountPasswordController extends AbstractController
{
    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/compte/modifier-mon-mot-de-passe", name="account_password")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder) : Response
    {
        $notification = null;
        $user = $this->getUser(); // on récupère les infos de l'utilisateur connecté
        $form = $this->createForm(ChangePasswordType::class, $user); // création du formulaire de modification du mot de passe
        
        // traitement du formulaire
        // on écoute la requête entrante du formulaire
        $form->handleRequest($request);

        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){
            // récupération du mot de passe actuel saisi par l'utilisateur
            $old_pwd = $form->get('old_password')->getData();

            // vérification du mot de passe saisie avec l'ancien stocké en bdd
            if($encoder->isPasswordValid($user, $old_pwd)){
                // récupération du nouveau mot de passe saisi par l'utilisateur
                $new_pwd = $form->get('new_password')->getData();
                // encodage du nouveau mot de passe saisi
                $password = $encoder->encodePassword($user, $new_pwd);
                // update du nouveau mot de passe cripté dans la bdd
                
                // on réinjecte le password encoder dans l'objet $user
                $user->setPassword($password);

                // on sauvegarde les données dans la table User
                $this->entityManager->persist($user); // fige les datas
                $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
            
                // Message pour avertir l'utilisateur que le mot de passe a bien été MAJ en BDD
                $notification = "Votre mot de passe a bien été mis à jour.";
            }else{
                $notification = "Votre mot de passe actuel n'est pas le bon.";
            }
            
        }
        
        return $this->render('account/password.html.twig',[
            'form' => $form->createView(),
            'notification' => $notification
        ]);

    }
}
