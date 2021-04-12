<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    /**
     * @Route("/nous-contacter", name="contact")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        //si le formulaire est soumis et qu'il est valide c'est-à-dire que les données renseignées correspondent aux type de champs définis dans le fichier RegisterType.php
        if($form->isSubmitted() && $form->isValid()){
            $this->addFlash('notice', "Merci de nous avoir contacté. Notre équipe va vous répondre dans les meilleurs délais.");
            $message = $form->getData();
            
            
            // Envoyer le message renseigné par un utilisateur à l'administrateur du site
            $mail = new Mail();
            $content = "Mail reçu de La Boutique Française - Contact<br/><br/><hr>";
            $content .= "Nom : ".$message['nom']. "<br/>";
            $content .= "Prénom : ".$message['prenom']."<br/>";
            $content .= "Email : ".$message['email']."<br/><br/><hr>";
            $content .= "Contenue : ".$message['content']."<br/>";
            $mail->send('p.dehem@gmail.com', $message['nom'].' '.$message['prenom'], 'Nouveau mail reçu', $content);
            
            // retour à la page d'aceuil une fois le mail parti
            return $this->redirectToRoute('home');
        }
        
        return $this->render('contact/index.html.twig',[
            'form' => $form->createView()
        ]);

    
    }
}
