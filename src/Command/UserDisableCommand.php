<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserDisableCommand extends Command
{
    protected static $defaultName = 'app:user:disable';
    protected static $defaultDescription = 'Disables a User';
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
        $user = $this->userService->disableUser($uuid);
        if ($user) {
            $io->success("Successfully disabled User \"{$user->getEmail()}\"");
        } else {
            $io->error('Could not disable User!');
        }

        return (int) Command::SUCCESS;
    }
}
