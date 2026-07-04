<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init:admin',
    description: 'Initializes the basic role hierarchy and assigns the initial admin.',
)]
class AppInitAdminCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Provider name (e.g., telegram)', 'telegram')
            ->addOption('subject-id', null, InputOption::VALUE_REQUIRED, 'Provider subject identifier (e.g., Telegram user ID)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $provider = $input->getOption('provider');
        $subjectId = $input->getOption('subject-id');

        $roleRepo = $this->em->getRepository(Role::class);
        $userRepo = $this->em->getRepository(User::class);
        $authorRepo = $this->em->getRepository(Author::class);

        // 1. Setup Roles
        $adminRole = $roleRepo->findOneBy(['roleName' => 'ROLE_ADMIN']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setRoleName('ROLE_ADMIN');
            $this->em->persist($adminRole);
        }

        $modRole = $roleRepo->findOneBy(['roleName' => 'ROLE_MODERATOR']);
        if (!$modRole) {
            $modRole = new Role();
            $modRole->setRoleName('ROLE_MODERATOR');
            $this->em->persist($modRole);
        }

        $creatorRole = $roleRepo->findOneBy(['roleName' => 'ROLE_CREATOR']);
        if (!$creatorRole) {
            $creatorRole = new Role();
            $creatorRole->setRoleName('ROLE_CREATOR');
            $this->em->persist($creatorRole);
        }

        $userRole = $roleRepo->findOneBy(['roleName' => 'ROLE_USER']);
        if (!$userRole) {
            $userRole = new Role();
            $userRole->setRoleName('ROLE_USER');
            $this->em->persist($userRole);
        }

        // 2. Build Hierarchy: ADMIN -> MODERATOR -> CREATOR -> USER
        if (!$adminRole->getChildren()->contains($modRole)) {
            $adminRole->addChild($modRole);
        }
        if (!$modRole->getChildren()->contains($creatorRole)) {
            $modRole->addChild($creatorRole);
        }
        if (!$creatorRole->getChildren()->contains($userRole)) {
            $creatorRole->addChild($userRole);
        }

        // Also fallback hierarchy like old setup: admin->user
        if (!$adminRole->getChildren()->contains($userRole)) {
            $adminRole->addChild($userRole);
        }
        if (!$modRole->getChildren()->contains($userRole)) {
            $modRole->addChild($userRole);
        }

        $this->em->flush();

        // 3. Setup Initial Admin User (via Identity)
        $identityRepo = $this->em->getRepository(\App\Entity\UserIdentity::class);
        $identity = $identityRepo->findOneBy(['providerName' => $provider, 'providerId' => (string) $subjectId]);

        if ($identity) {
            $user = $identity->getUser();
        } else {
            $user = new User();
            $this->em->persist($user);

            $identity = new \App\Entity\UserIdentity();
            $identity->setUser($user);
            $identity->setProviderName($provider);
            $identity->setProviderId((string) $subjectId);
            $this->em->persist($identity);
        }

        if ($user->getUserRoles()->contains($adminRole)) {
            $io->warning(sprintf('User linked to %s ID %s already has ROLE_ADMIN.', $provider, $subjectId));
        } else {
            $user->addRole($adminRole);
            $io->success(sprintf('Assigned ROLE_ADMIN to %s ID %s.', $provider, $subjectId));
        }

        // 4. Setup Initial Author Profile
        $author = $authorRepo->findOneBy(['user' => $user]);
        if (!$author) {
            $author = new Author();
            $author->setName('Admin #'.$telegramId);
            $author->setState('private');
            $author->setUser($user);
            $this->em->persist($author);
            $io->success(sprintf('Created private Author profile for Telegram ID %d.', $telegramId));
        }

        $this->em->flush();

        $io->success('Initialization complete.');

        return Command::SUCCESS;
    }
}
