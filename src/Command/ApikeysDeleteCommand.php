<?php

namespace App\Command;

use App\Service\ApiKeyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApikeysDeleteCommand extends Command
{
    protected static $defaultName = 'app:apikeys:delete';
    protected static $defaultDescription = 'Deletes the specified API Key';

    public function __construct(private readonly ApiKeyService $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name for the API Key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if ($this->apiKeyService->deleteApiKey($name)) {
            $io->success("API Key \"{$name}\" successfully deleted!");
        } else {
            $io->error("Could not delete API Key \"{$name}\"");
        }

        return (int) Command::SUCCESS;
    }
}
