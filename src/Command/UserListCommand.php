<?php

namespace App\Command;

use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserListCommand extends Command
{
    protected static $defaultName = 'app:user:list';
    protected static $defaultDescription = 'Lists all or one User';

    public function __construct(private readonly USerService $userService)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Gets User based on UUID')
            ->addOption('externId', null, InputOption::VALUE_REQUIRED, 'Gets User based on externID')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Gets User based on EMail')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all users include disabled.')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Shows all Parameters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $disabled = $input->getOption('all');

        if ($input->getOption('uuid')) {
            $users = $this->userService->listUser('uuid', $input->getOption('uuid'), $disabled);
        } elseif ($input->getOption('externId')) {
            $users = $this->userService->listUser('externId', $input->getOption('externId'), $disabled);
        } elseif ($input->getOption('email')) {
            $users = $this->userService->listUser('email', $input->getOption('email'), $disabled);
        } else {
            $users = $this->userService->listUser(null, null, $disabled);
        }

        if ($users) {
            $table = new Table($output);
            if ($input->getOption('detailed')) {
                $table->setHeaders([
                    'Id', 'EMail', 'Nickname', 'UUID', 'Status', 'Firstname', 'Surname', 'Postcode', 'City',
                    'Country', 'Phone', 'Gender', 'infoMails', 'emailConfirmed',
                    'Superadmin', 'registeredAt', 'modifiedAt',
                ]);
                foreach ($users as $key) {
                    $table->addRow([
                        $key->getId(),
                        $key->getEmail(),
                        $key->getNickname(),
                        $key->getUuid(),
                        $key->getStatus(),
                        $key->getFirstname(),
                        $key->getSurname(),
                        $key->getPostcode(),
                        $key->getCity(),
                        $key->getCountry(),
                        $key->getPhone(),
                        $key->getGender(),
                        $key->getInfoMails() ? 'Yes' : 'No',
                        $key->getEmailConfirmed() ? 'Yes' : 'No',
                        $key->getIsSuperadmin() ? 'Yes' : 'No',
                        $key->getRegisteredAt()->format('d.m.Y H:i'),
                        $key->getModifiedAt()->format('d.m.Y H:i'),
                    ]);
                }
            } else {
                $table->setHeaders(['Id', 'EMail', 'Nickname', 'UUID']);
                foreach ($users as $key) {
                    $table->addRow([$key->getId(), $key->getEmail(), $key->getNickname(), $key->getUuid()]);
                }
            }
            $table->render();
        } else {
            $io->error('No Users have been found with the supplied Searchparameters');
        }

        return (int) Command::SUCCESS;
    }
}
