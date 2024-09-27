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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

#[AsCommand(
    name: 'vis:user:removerole',
    description: 'Remove a role from a vis user',
)]
class VisUserRemoveRoleCommand extends Command
{
    protected bool $error = false;

    protected string $errorMessage = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $validator = Validation::createValidator();
        $emailConstraint = new Email();

        while (true) {
            $ioInput = $io->ask('Please enter your email (or type "quit" to exit): ');
            $email = is_string($ioInput) ? trim($ioInput) : '';

            if ('quit' === strtolower((string) $email)) {
                $io->comment('Command aborted by user.');

                return Command::SUCCESS;
            }

            $violations = $validator->validate($email, $emailConstraint);

            if (0 === count($violations)) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if (null === $user) {
                    $io->error('User with email '.$email.' not found.');

                    return Command::FAILURE;
                }

                while (true) {
                    $ioInput = $io->ask('Please enter the role to remove (or type "quit" to exit): ');
                    $role = is_string($ioInput) ? trim($ioInput) : '';

                    if ('quit' === strtolower((string) $role)) {
                        $io->comment('Command aborted by user.');

                        return Command::SUCCESS;
                    }

                    if (!in_array($role, $user->getRoles(), true)) {
                        $io->error('Role '.$role.' not found in user with email '.$email.'.');

                        return Command::FAILURE;
                    }

                    $user->removeRole($role);

                    try {
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        $io->success('Role '.$role.' removed from user with email '.$email.' successfully.');

                        return Command::SUCCESS;
                    } catch (\Exception) {
                        $io->error('An error occurred while removing role '.$role.' from user with email '.$email.'.');

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
