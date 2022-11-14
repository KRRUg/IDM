<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserDeleteCommand extends Command
{
    protected static $defaultName = 'app:user:delete';
    protected static $defaultDescription = 'Deletes a User';
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(USerService $userService)
    {
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('uuid', InputArgument::REQUIRED, 'UUID from User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $uuid = $input->getArgument('uuid');
        $user = $this->userService->getUser($uuid);
        if ($io->confirm("Would you like to delete the User \"{$user->getEmail()}\" ?", false)) {
            $deleted = $this->userService->deleteUser($uuid);
            if ($deleted) {
                $io->success("Successfully deleted User \"{$user->getEmail()}\"");
            } else {
                $io->error('Could not delete User!');
            }
        } else {
            $io->warning('Aborted Userdeletion');
        }

        return (int) Command::SUCCESS;
    }
}
