<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Joueur')
            ->setEntityLabelInPlural('Joueurs')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['firstName', 'lastName', 'email', 'city'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        // ── Identifiants (lecture seule) ───────────────────────────
        yield IdField::new('id', 'ID')->onlyOnIndex();
        yield IntegerField::new('publicId', 'ID public')->onlyOnDetail();

        // ── Identité ──────────────────────────────────────────────
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield EmailField::new('email', 'Email');

        // ── Mot de passe (formulaire uniquement) ──────────────────
        yield TextField::new('plainPassword', 'Mot de passe')
            ->setRequired(false)
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setHelp('Laisser vide pour ne pas modifier');

        // ── Profil ────────────────────────────────────────────────
        yield TextField::new('city', 'Ville');
        yield ChoiceField::new('fftRanking', 'Classement FFT')
            ->setChoices(array_combine(
                ['NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
                 '15/5', '15/4', '15/3', '15/2', '15/1', '15',
                 '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30'],
                ['NC', '40', '30/5', '30/4', '30/3', '30/2', '30/1', '30',
                 '15/5', '15/4', '15/3', '15/2', '15/1', '15',
                 '4/6', '3/6', '2/6', '1/6', '0', '-2/6', '-4/6', '-15', '-30']
            ))
            ->setRequired(false);
        yield ChoiceField::new('gender', 'Genre')
            ->setChoices(['Homme' => 'M', 'Femme' => 'F', 'Autre' => 'A'])
            ->setRequired(false);
        yield DateField::new('birthdate', 'Date de naissance')
            ->setRequired(false)
            ->hideOnIndex();
        yield ChoiceField::new('handedness', 'Latéralité')
            ->setChoices(['Droitier(e)' => 'R', 'Gaucher(e)' => 'L'])
            ->setRequired(false)
            ->hideOnIndex();
        yield BooleanField::new('hasCourt', 'Accès terrain')
            ->hideOnIndex()
            ->setRequired(false);
        yield ChoiceField::new('preferredSurface', 'Surfaces préférées')
            ->setChoices(['Dur' => 'hard', 'Terre battue' => 'clay', 'Gazon' => 'grass', 'Moquette' => 'carpet'])
            ->allowMultipleChoices()
            ->setRequired(false)
            ->hideOnIndex();
        yield TextareaField::new('description', 'Description')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('avatar', 'Avatar (chemin)')
            ->setRequired(false)
            ->hideOnIndex();

        // ── Préférences / notifications ───────────────────────────
        yield BooleanField::new('acceptMessages', 'Accepte les messages')->hideOnIndex();
        yield BooleanField::new('notifyMessages', 'Notif. messages')->hideOnIndex();
        yield BooleanField::new('notifyProposalReplies', 'Notif. réponses annonces')->hideOnIndex();
        yield BooleanField::new('acceptPrivateProposals', 'Accepte parties privées')->hideOnIndex();

        // ── Modération ────────────────────────────────────────────
        yield BooleanField::new('isSuspended', 'Suspendu');
        yield ArrayField::new('roles', 'Rôles')->hideOnIndex();

        // ── Dates (lecture seule) ──────────────────────────────────
        yield DateTimeField::new('createdAt', 'Inscrit le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield DateTimeField::new('lastActivityAt', 'Dernière activité')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('city', 'Ville'))
            ->add(ChoiceFilter::new('gender', 'Genre')->setChoices(['Homme' => 'M', 'Femme' => 'F', 'Autre' => 'A']))
            ->add(BooleanFilter::new('isSuspended', 'Suspendu'))
            ->add(BooleanFilter::new('acceptMessages', 'Accepte messages'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $makeAdmin = Action::new('makeAdmin', 'Promouvoir admin', 'fa fa-shield')
            ->linkToCrudAction('makeAdmin')
            ->displayIf(fn(User $u) => !in_array('ROLE_ADMIN', $u->getRoles(), true));

        $revokeAdmin = Action::new('revokeAdmin', 'Révoquer admin', 'fa fa-shield-xmark')
            ->linkToCrudAction('revokeAdmin')
            ->addCssClass('text-danger')
            ->displayIf(fn(User $u) => in_array('ROLE_ADMIN', $u->getRoles(), true));

        return $actions
            ->add(Crud::PAGE_INDEX, $makeAdmin)
            ->add(Crud::PAGE_INDEX, $revokeAdmin)
            ->add(Crud::PAGE_DETAIL, $makeAdmin)
            ->add(Crud::PAGE_DETAIL, $revokeAdmin)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW);
    }

    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    private function hashPasswordIfProvided(mixed $user): void
    {
        if (!$user instanceof User) return;
        $plain = $this->getContext()->getRequest()->request->all()['User']['plainPassword'] ?? null;
        if ($plain) {
            $user->setPassword($this->hasher->hashPassword($user, $plain));
        }
    }

    #[AdminRoute('/admin/user/{entityId}/make-admin', name: 'admin_user_make_admin')]
    public function makeAdmin(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $generator): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->setRoles(array_unique([...$user->getRoles(), 'ROLE_ADMIN']));
        $em->flush();
        $this->addFlash('success', "{$user->getEmail()} est maintenant administrateur.");
        return $this->redirect($generator->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/admin/user/{entityId}/revoke-admin', name: 'admin_user_revoke_admin')]
    public function revokeAdmin(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $generator): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->setRoles(array_values(array_filter($user->getRoles(), fn($r) => $r !== 'ROLE_ADMIN')));
        $em->flush();
        $this->addFlash('success', "Rôle admin retiré à {$user->getEmail()}.");
        return $this->redirect($generator->setAction(Action::INDEX)->generateUrl());
    }
}
