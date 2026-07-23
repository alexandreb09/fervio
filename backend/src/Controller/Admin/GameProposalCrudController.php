<?php

namespace App\Controller\Admin;

use App\Entity\GameProposal;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class GameProposalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GameProposal::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Annonce')
            ->setEntityLabelInPlural('Annonces')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'city', 'author.firstName', 'author.lastName']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', 'Titre');
        yield TextField::new('city', 'Ville');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(['Disponible' => 'open', 'Complet' => 'full', 'Annulée' => 'cancelled']);
        yield ChoiceField::new('joinMode', 'Mode d\'inscription')
            ->setChoices(['Automatique' => 'auto', 'Validation manuelle' => 'approval'])
            ->hideOnIndex();
        yield ChoiceField::new('gameType', 'Type')
            ->setChoices(['Simple' => 'simple', 'Double' => 'double', 'Double mixte' => 'double_mixte'])
            ->allowMultipleChoices(false);
        yield IntegerField::new('maxPlayers', 'Max joueurs')->hideOnIndex();
        yield BooleanField::new('isPrivate', 'Privée');
        yield DateTimeField::new('scheduledAt', 'Date prévue')->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('createdAt', 'Créée le')->onlyOnIndex()->setFormat('dd/MM/yyyy');
        yield TextareaField::new('description', 'Description')->onlyOnDetail()->setNumOfRows(4);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(['Disponible' => 'open', 'Complet' => 'full', 'Annulée' => 'cancelled']))
            ->add(TextFilter::new('city', 'Ville'))
            ->add(ChoiceFilter::new('gameType', 'Type')->setChoices(['Simple' => 'simple', 'Double' => 'double', 'Double mixte' => 'double_mixte']));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
