<?php

namespace App\Command;

use App\Service\ApiKeyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApikeysCreateCommand extends Command
{
    protected static $defaultName = 'app:apikeys:create';
    protected static $defaultDescription = 'Adds the specified APIKey to the allowed Keys';

    public function __construct(private readonly ApiKeyService $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name for the API Key')
            ->addArgument('apikey', InputArgument::REQUIRED, 'API Key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $apikey = $input->getArgument('apikey');

        if ($this->apiKeyService->createApiKey($name, $apikey)) {
            $io->success("API Key \"{$name}\" successfully created!");
        } else {
            $io->error("Could not create API Key \"{$name}\"");
        }

        return (int) Command::SUCCESS;
    }
}
