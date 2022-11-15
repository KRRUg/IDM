<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserEnableCommand extends Command
{
    protected static $defaultName = 'app:user:enable';
    protected static $defaultDescription = 'Enables a User';

    public function __construct(private readonly USerService $userService)
    {
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
        $user = $this->userService->enableUser($uuid);
        if ($user) {
            $io->success("Successfully enabled User \"{$user->getEmail()}\"");
        } else {
            $io->error('Could not enable User!');
        }

        return (int) Command::SUCCESS;
    }
}
