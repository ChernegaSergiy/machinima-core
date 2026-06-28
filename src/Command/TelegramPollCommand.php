<?php

namespace App\Command;

use morfeditorial\MyBot;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Morfeditorial\TelegramBotBundle\Client\TelegramClient;
use Morfeditorial\TelegramBotBundle\Routing\UpdateDispatcher;

#[AsCommand(
    name: 'app:telegram:poll',
    description: 'Runs the Telegram bot using long-polling',
)]
class TelegramPollCommand extends Command
{
    private int $offset = 0;

    public function __construct(
        private TelegramClient $telegramClient,
        private UpdateDispatcher $updateDispatcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Starting Telegram Bot Polling...');

        while (true) {
            try {
                $updates = $this->telegramClient->getUpdates($this->offset);
                foreach ($updates as $update) {
                    $io->writeln('Received update: ' . ($update['update_id'] ?? 'unknown'));
                    $this->offset = ($update['update_id'] ?? 0) + 1;
                    $this->updateDispatcher->dispatch($update);
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
