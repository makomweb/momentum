<?php

namespace App\Command;

use App\AI\RAG\MyAgent;
use App\AI\RAG\NeuronAdapter;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:my-agent',
    description: 'Run my agent',
)]
class MyAgentCommand extends Command
{
    public function __construct(private NeuronAdapter $adapter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'question',
                InputArgument::OPTIONAL,
                'The question or prompt to ask'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        MyAgent::initialize($this->adapter);

        try {
            $question = $input->getArgument('question') ?? 'What is PHP?';
            assert(is_string($question));

            $io->writeln('🤖 MyAgent: '.$question);
            $io->newLine();

            $stream = MyAgent::make()
                ->setThreadId('chat_'.time())
                ->stream(new UserMessage($question));

            foreach ($stream as $chunk) {
                if ($chunk instanceof TextChunk) {
                    $io->write($chunk->content);
                }
            }

            $io->newLine();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error communicating with Ollama: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
