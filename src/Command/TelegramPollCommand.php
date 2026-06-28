<?php

namespace App\Command;

use morfeditorial\MyBot;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(
    name: 'app:telegram:poll',
    description: 'Runs the Telegram bot using long-polling',
)]
class TelegramPollCommand extends Command
{
    public function __construct(
        private string $botToken,
        private ContainerInterface $container
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Starting Telegram Bot Polling...');

        $bot = new MyBot($this->botToken, $this->container);

        while (true) {
            try {
                $updates = $bot->getUpdates();
                foreach ($updates as $update) {
                    $io->writeln('Received update: ' . ($update['update_id'] ?? 'unknown'));
                    $bot->handleUpdate($update);
                }
                sleep(1);
            } catch (\Exception $e) {
                $io->error($e->getMessage());
                sleep(5);
            }
        }

        return Command::SUCCESS;
    }
}
