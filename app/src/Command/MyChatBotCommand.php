<?php

namespace App\Command;

use App\AI\RAG\MyChatbot;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:my-chatbot',
    description: 'Run my chatbot',
)]
class MyChatBotCommand extends Command
{
    public function __construct(private MyChatbot $chatbot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('strava_user_id', InputArgument::REQUIRED, 'Strava user ID')
            ->addArgument('question', InputArgument::REQUIRED, 'The question or prompt to ask');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));
        $stravaUserId = (int) $stravaUserId;

        $this->chatbot->setStravaUserId($stravaUserId);

        try {
            $question = $input->getArgument('question') ?? 'What is PHP?';
            assert(is_string($question));

            $io->writeln('🤖 MyChatbot: '.$question);
            $io->newLine();

            $stream = $this->chatbot
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
            $io->error('Error communicating with chat bot: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
