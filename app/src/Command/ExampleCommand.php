<?php

namespace App\Command;

use App\Async\Message\ExampleMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:example',
    description: 'Example command that dispatches an async message',
)]
class ExampleCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = new ExampleMessage(123, 'Example Task');
        $this->bus->dispatch($message);

        $output->writeln('<info>✓ Message dispatched to async queue</info>');
        $output->writeln('  Check worker logs: docker compose logs -f worker');

        return Command::SUCCESS;
    }
}
