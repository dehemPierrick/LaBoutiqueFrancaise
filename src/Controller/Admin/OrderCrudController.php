<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Classe\Mail;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Admin\OrderCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class OrderCrudController extends AbstractCrudController
{
    // création d'une variable pour doctrine
    private $entityManager;

    private $crudUrlGenerator;

    // création du constructeur
    public function __construct(EntityManagerInterface $entityManager, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    // fonction qui permet de trier par ordre des dernières entrées en base et de l'afficher dans easyAdmin
    public function configureCrud(Crud $crud):Crud{
        return $crud->setDefaultSort(['id' => 'DESC']);
    }

    // permet de modifier ou gérer les différentes actions
    public function configureActions(Actions $actions):Actions
    {
        $updatePreparation = Action::new('updatePreparation', 'Préparation en cours','fas fa-box-open' )->linkToCrudAction('updatePreparation');
        $updateDelivery = Action::new('updateDelivery', 'Livraison en cours','fas fa-truck' )->linkToCrudAction('updateDelivery');
        
        
        return $actions
        ->add('detail', $updatePreparation)
        ->add('detail', $updateDelivery)
        ->add('index', 'detail')
        ->remove('index', 'edit')
        ->remove('index', 'delete');
    }
    
    public function updatePreparation(AdminContext $context)
    {
        $order = $context->getEntity()->getInstance();
        $order->setState(2); // pour passage en mode préparation en cours
        $this->entityManager->flush();

        $this->addFlash('notice', "<span style='color:green'><strong>La commande ".$order->getReference()." est bien <u>en cours de préparation</u></strong></span>");
        // rediriger l'utilisateur dans la vue index
        $url = $this->crudUrlGenerator->build()
                    ->setController(OrderCrudController::class)
                    ->setAction('index')
                    ->generateUrl();


        // Envoyer un email à notre client pour lui indiquer que sa commande est en cours de préparation
        $mail = new Mail();
        $content = "Bonjour ".$order->getUser()->getFirstName()."<br/>La commande ".$order->getReference()." est en cours de préparation.";
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstName(), "Commande ".$order->getReference()." - En cours de préparation.", $content );
        

        return $this->redirect($url);
    }

    public function updateDelivery(AdminContext $context)
    {
        $order = $context->getEntity()->getInstance();
        $order->setState(3); // pour passage en mode préparation en cours
        $this->entityManager->flush();

        $this->addFlash('notice', "<span style='color:orange'><strong>La commande ".$order->getReference()." est bien <u>en cours de livraison</u></strong></span>");
        // rediriger l'utilisateur dans la vue index
        $url = $this->crudUrlGenerator->build()
                    ->setController(OrderCrudController::class)
                    ->setAction('index')
                    ->generateUrl();
       
        // Envoyer un email à notre client pour lui indiquer que sa commande est en cours de livraison
        $mail = new Mail();
        $content = "Bonjour ".$order->getUser()->getFirstName()."<br/>La commande ".$order->getReference()." est en cours de livraison.";
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstName(), "Commande ".$order->getReference()." - En cours de livraison.", $content );
        
        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            DateTimeField::new('createdAt', "Passé le"),
            TextField::new('user.getFullName',"Prénom Nom"),
            TextEditorField::new('delivery', 'Adresse de livraison')->onlyOnDetail(),
            MoneyField::new('total', 'Total Produit')->setCurrency('EUR'),
            TextField::new('carrierName',"Transporteur"),
            MoneyField::new('carrierPrice', 'Frais de port')->setCurrency('EUR'),
            ChoiceField::new('state')->setChoices([
                'Non Payée' => 0,
                'Payée ' => 1,
                'Préparation en cours' => 2,
                'Livraison en cours' => 3

            ]),
            ArrayField::new('orderDetails','Produits achetés')->hideOnIndex()
        ];
    }
    
}
