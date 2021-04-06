<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class RegisterController extends AbstractController
{

    // création d'une variable pour doctrine
    private $entityManager;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request,UserPasswordEncoderInterface $encoder): Response
    {
        $notification = null;
        //instancie la classe User
        $user = new User();

        //instancie le formulaire
        $form = $this->createForm(RegisterType::class, $user);

        // on écoute a requête entrante du formulaire
        $form->handleRequest($request);

        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){

            //on récupère les données du formulaire
            $user = $form->getData();
            
            // vérification si l'utilisateur n'est pas déjà enregistrer
            $search_email = $this->entityManager->getRepository(User::class)->findOneByEmail($user->getEmail());
            if (!$search_email){
                // on encode le mot de passe récupérer dans l'objet $user
                $password = $encoder->encodePassword($user, $user->getPassword());

                // on réinjecte le password encoder dans l'objet $user
                $user->setPassword($password);

                // on sauvegarde les données dans la table User
                $this->entityManager->persist($user); // fige les datas
                $this->entityManager->flush(); // exécute la persistance et enregistrement en bdd
                
                // envoi du mail d'inscription à l'utilisateur
                $mail = new Mail();
                $content = "Bonjour ".$user->getFirstName()."<br/>Bienvenue sur la première boutique dédiée au made in France.<br/>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec quis libero rutrum justo interdum posuere. Pellentesque ullamcorper eros sapien, in fringilla leo consectetur eu. Donec tincidunt turpis id consequat eleifend. Phasellus porttitor consequat nunc, id facilisis eros dictum et. Vestibulum a massa condimentum, semper dui in, faucibus dui. Sed eget lacinia ante. Maecenas ut diam mi. Maecenas suscipit ullamcorper tortor, sed aliquam purus elementum non.";
                $mail->send($user->getEmail(), $user->getFirstName(), "Bienvenue sur La Boutique Française", $content );
                
                $notification = "Votre inscription s'est correctement déroulée. Vous pouvez dès à présent vous connecter à votre compte.";

            }else{
                $notification = "L'email que vous avez renseigné existe déjà.";
            }
            
            
            
        }

        return $this->render('register/index.html.twig',[
            'form' => $form->createView(),
            'notification' => $notification
        ]);
    }
}
