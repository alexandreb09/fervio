<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Fervio — Admin')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToRoute('Joueurs', 'fa fa-users', 'admin_user_index');
        yield MenuItem::section('Contenu');
        yield MenuItem::linkToRoute('Annonces', 'fa fa-calendar', 'admin_game_proposal_index');
        yield MenuItem::linkToRoute('Messages', 'fa fa-envelope', 'admin_message_index');
        yield MenuItem::section('Modération');
        yield MenuItem::linkToRoute('Signalements', 'fa fa-flag', 'admin_report_index');
        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left', 'https://fervio.fr/');
    }
}
