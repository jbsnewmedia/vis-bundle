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
    name: 'vis:user:create',
    description: 'Create a new vis user',
)]
class VisUserCreateCommand extends Command
{
    protected bool $error = false;

    protected string $errorMessage = '';

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
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
                while (true) {
                    $password = $io->askHidden('Please enter your password (at least 6 characters): ');

                    $passwordViolations = $validator->validate($password, new Length(['min' => 6]));

                    if (0 === count($passwordViolations)) {
                        $user = new User();
                        $user->setEmail($email);
                        $user->setPassword(
                            $this->userPasswordHasher->hashPassword(
                                $user,
                                $password
                            )
                        );

                        try {
                            $this->entityManager->persist($user);
                            $this->entityManager->flush();
                            $io->success('User with email '.$email.' created successfully.');
                        } catch (\Exception $e) {
                            $io->error('An error occurred while creating the user: '.$e->getMessage());
                        }

                        return Command::SUCCESS;
                    } else {
                        foreach ($passwordViolations as $violation) {
                            $io->error((string) $violation->getMessage());
                        }
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
