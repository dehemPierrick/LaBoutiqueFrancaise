<?php

namespace App\Controller;

use DateTime;
use App\Classe\Mail;
use App\Entity\User;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ResetPasswordController extends AbstractController
{
    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
  
   
        // si l'utilisateur est déjà connecter on bloque l'accès à la page de réinitialisation
        if($this->getUser()){
            return $this->redirectToRoute('home');
        }

        // si l'email a été envoyé
        if($request->get('email')){
            // vérification si l'email existe en base de donnée
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));
            
            // si l'utilisateur existe bien en bdd
            if($user){
                // étape 1 : enregistrer en base de donnée la demande de reset_password avec user, token, createdAt
                $reset_password = new ResetPassword();
                $reset_password->setUser($user); 
                $reset_password->setToken(uniqid()); //création d'un token
                $reset_password->setCreatedAt(new DateTime()); // création de la date 
                $this->entityManager->persist($reset_password); 
                $this->entityManager->flush();

                // étape 2 : envoi de l'email à l'utilisateur avec un lien lui permettant de mettre à jour son mot de passe
                $mail = new Mail();
                $url = $this->generateUrl('update_password',[
                        'token' =>$reset_password->getToken()
                    ]);
                $content = "Bonjour ".$user->getFirstname().",<br/>Vous avez demandé à réinitisaliser votre mot de passe sur le site La Boutique Française.<br/><br/>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe.</a>";
                $mail->send($user->getEmail(), $user->getFirstname().' '.$user->getLastname(), 'Réinitialiser votre mot de passe sur La Boutique Française', $content);
                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');
           
            }else{
                $this->addFlash('notice', "Cette adresse email est inconnue.");
            }
        
        }
        return $this->render('reset_password/index.html.twig');
    }


    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="update_password")
     */
    public function update_password(Request $request, UserPasswordEncoderInterface $encoder, $token): Response
    {
        // on récupère le token 
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);
    
        // si le token n'existe pas 
        if(!$reset_password){
            return $this->redirectToRoute('reset_password');
        }
        
        // Vérifier le createdAt est égale à maintenant - 3h
        $now = new DateTime();
        if($now > $reset_password->getCreatedAt()->modify('+ 3 hour')){
            // le token a espiré
            $this->addFlash('notice', "Votre demande de mot de passe a expirée. Merci de la renouveller.");
            return $this->redirectToRoute('reset_password');
        }

        // Rendre une vue avec mot de passe et confirmez votre mot de passe.
        $form = $this->createForm(ResetPasswordType::class);//instancie le formulaire
        $form->handleRequest($request); // on écoute a requête entrante du formulaire
         
        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){
            // on récupère le mot de passe saisie
            $new_pwd = $form->get('new_password')->getData();
            
            // Encodage des mots de passe
            $password = $encoder->encodePassword($reset_password->getUser(), $new_pwd);
            $reset_password->getUser()->setPassword($password);

            // on sauvegarde les données dans la table User
            $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
        
            // redirection de l'utilisateur vers la page de connexion

            $this->addFlash('notice', 'Votre mot de passe a bien été mise à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig',[
            'form' =>$form->createView()
        ]);
        
    }
}
