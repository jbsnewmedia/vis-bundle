<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\VisBundle\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validation;

#[AsCommand(
    name: 'vis:user:addrole',
    description: 'Add a role to a vis user',
)]
class VisUserAddRoleCommand extends Command
{
    protected bool $error = false;

    protected string $errorMessage = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $validator = Validation::createValidator();
        $emailConstraint = new Email();

        while (true) {
            $email = $io->ask('Please enter your email (or type "quit" to exit): ');

            if ('quit' === strtolower((string) $email)) {
                $io->comment('Command aborted by user.');

                return Command::SUCCESS;
            }

            $violations = $validator->validate($email, $emailConstraint);

            if (0 === count($violations)) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($user === null) {
                    $io->error('User with email '.$email.' not found.');

                    return Command::FAILURE;
                }

                while (true) {
                    $role = $io->ask('Please enter the role to add (or type "quit" to exit): ');

                    if ('quit' === strtolower((string) $role)) {
                        $io->comment('Command aborted by user.');

                        return Command::SUCCESS;
                    }

                    $user->addRole($role);

                    try {
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        $io->success('Role '.$role.' added to user with email '.$email.' successfully.');

                        return Command::SUCCESS;
                    } catch (\Exception $e) {
                        $io->error('An error occurred while adding role '.$role.' to user with email '.$email.'.');

                        return Command::FAILURE;
                    }
                }
            } else {
                foreach ($violations as $violation) {
                    $io->error((string) $violation->getMessage());
                }
            }
        }
    }
}
