<?php

namespace App\Controller\Admin;

use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Report::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Signalement')
            ->setEntityLabelInPlural('Signalements')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['reporter.email', 'reportedUser.email', 'reason']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('reporter.email', 'Signalé par')->onlyOnIndex();
        yield TextField::new('reportedUser.firstName', 'Joueur signalé');
        yield TextField::new('reportedUser.email', 'Email signalé')->onlyOnDetail();
        yield ChoiceField::new('targetType', 'Type')
            ->setChoices(['Profil' => 'user', 'Annonce' => 'proposal', 'Message' => 'message']);
        yield IntegerField::new('targetId', 'ID cible');
        yield ChoiceField::new('category', 'Catégorie')
            ->setChoices(array_flip(Report::CATEGORIES));
        yield TextareaField::new('reason', 'Raison')->onlyOnDetail()->setNumOfRows(3);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(['En attente' => 'pending', 'Ignoré' => 'dismissed', 'Confirmé' => 'confirmed'])
            ->renderAsBadges(['pending' => 'warning', 'dismissed' => 'success', 'confirmed' => 'danger']);
        yield DateTimeField::new('createdAt', 'Date')->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(['En attente' => 'pending', 'Ignoré' => 'dismissed', 'Confirmé' => 'confirmed']))
            ->add(ChoiceFilter::new('targetType', 'Type')->setChoices(['Profil' => 'user', 'Annonce' => 'proposal', 'Message' => 'message']))
            ->add(ChoiceFilter::new('category', 'Catégorie')->setChoices(array_flip(Report::CATEGORIES)));
    }

    public function configureActions(Actions $actions): Actions
    {
        $dismiss = Action::new('dismiss', 'Ignorer', 'fa fa-check')
            ->linkToCrudAction('dismiss')
            ->displayIf(fn(Report $r) => $r->getStatus() === 'pending');

        $confirm = Action::new('confirm', 'Confirmer + suspendre', 'fa fa-ban')
            ->linkToCrudAction('confirm')
            ->addCssClass('text-danger')
            ->displayIf(fn(Report $r) => $r->getStatus() === 'pending');

        $unsuspend = Action::new('unsuspend', 'Réactiver compte', 'fa fa-unlock')
            ->linkToCrudAction('unsuspend')
            ->displayIf(fn(Report $r) => $r->getReportedUser()?->isSuspended() === true);

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $dismiss)
            ->add(Crud::PAGE_INDEX, $confirm)
            ->add(Crud::PAGE_INDEX, $unsuspend)
            ->add(Crud::PAGE_DETAIL, $dismiss)
            ->add(Crud::PAGE_DETAIL, $confirm)
            ->add(Crud::PAGE_DETAIL, $unsuspend);
    }

    #[AdminRoute('/admin/report/{entityId}/dismiss', name: 'admin_report_dismiss')]
    public function dismiss(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $gen): RedirectResponse
    {
        /** @var Report $report */
        $report = $context->getEntity()->getInstance();
        $report->setStatus('dismissed');
        $em->flush();
        $this->addFlash('success', 'Signalement ignoré.');
        return $this->redirect($gen->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/admin/report/{entityId}/confirm', name: 'admin_report_confirm')]
    public function confirm(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $gen): RedirectResponse
    {
        /** @var Report $report */
        $report = $context->getEntity()->getInstance();
        $report->setStatus('confirmed');
        $reportedUser = $report->getReportedUser();
        if ($reportedUser) {
            $reportedUser->setIsSuspended(true);
        }
        $em->flush();
        $this->addFlash('success', 'Signalement confirmé. Compte suspendu.');
        return $this->redirect($gen->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/admin/report/{entityId}/unsuspend', name: 'admin_report_unsuspend')]
    public function unsuspend(AdminContext $context, EntityManagerInterface $em, AdminUrlGenerator $gen): RedirectResponse
    {
        /** @var Report $report */
        $report = $context->getEntity()->getInstance();
        $reportedUser = $report->getReportedUser();
        if ($reportedUser) {
            $reportedUser->setIsSuspended(false);
        }
        $em->flush();
        $this->addFlash('success', "Compte de {$reportedUser?->getEmail()} réactivé.");
        return $this->redirect($gen->setAction(Action::INDEX)->generateUrl());
    }
}
