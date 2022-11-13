<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserEditCommand extends Command
{
    protected static $defaultName = 'app:user:edit';
    protected static $defaultDescription = 'Edits a User';
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('uuid', InputArgument::REQUIRED, 'UUID of User to be edited')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'New EMail Address')
            ->addOption('confirmed', null, InputOption::VALUE_REQUIRED, 'Change emailConfirmed Flag (true/false)')
            ->addOption('superadmin', null, InputOption::VALUE_REQUIRED, 'Change Superadmin Flag (true/false)')
            ->addOption('infoMails', null, InputOption::VALUE_REQUIRED, 'Change infoMails Flag (true/false)')
            ->addOption('postcode', null, InputOption::VALUE_REQUIRED, 'Edit Postcode')
            ->addOption('nickname', null, InputOption::VALUE_REQUIRED, 'Edit Nickname')
            ->addOption('firstname', null, InputOption::VALUE_REQUIRED, 'Edit Firstname')
            ->addOption('surname', null, InputOption::VALUE_REQUIRED, 'Edit Surname')
            ->addOption('city', null, InputOption::VALUE_REQUIRED, 'Edit City')
            ->addOption('country', null, InputOption::VALUE_REQUIRED, 'Edit Country')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'Edit Phone')
            ->addOption('gender', null, InputOption::VALUE_REQUIRED, 'Edit Gender')
            ->addOption('birthdate', null, InputOption::VALUE_REQUIRED, 'Edit Birthdate');
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Edit Website')
            ->addOption('steamAccount', null, InputOption::VALUE_REQUIRED, 'Edit steamAccount')
            ->addOption('hardware', null, InputOption::VALUE_REQUIRED, 'Edit Hardware')
            ->addOption('statements', null, InputOption::VALUE_REQUIRED, 'Edit Statements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userdata = $input->getOptions();
        $userdata['uuid'] = $input->getArgument('uuid');

        $user = $this->userService->editUser($userdata);
        if ($user) {
            $io->success("Successfully edited User \"{$user->getEmail()}\" !");
        } else {
            $io->error("Could not edit User \"{$input->getArgument('uuid')}\" !");
        }

        return (int) Command::SUCCESS;

        }
}
