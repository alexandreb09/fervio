<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:make-admin',
    description: 'Promote a user to ROLE_ADMIN (or demote with --revoke)',
)]
class MakeAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote')
            ->addOption('revoke', null, null, 'Revoke ROLE_ADMIN instead of granting it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $revoke = $input->getOption('revoke');

        $user = $this->userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('No user found with email "%s".', $email));
            return Command::FAILURE;
        }

        $roles = $user->getRoles();

        if ($revoke) {
            $roles = array_values(array_filter($roles, fn($r) => $r !== 'ROLE_ADMIN'));
            $user->setRoles($roles);
            $this->em->flush();
            $io->success(sprintf('ROLE_ADMIN revoked from %s.', $email));
        } else {
            if (in_array('ROLE_ADMIN', $roles, true)) {
                $io->warning(sprintf('%s already has ROLE_ADMIN.', $email));
                return Command::SUCCESS;
            }
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $this->em->flush();
            $io->success(sprintf('%s is now an administrator.', $email));
        }

        return Command::SUCCESS;
    }
}
