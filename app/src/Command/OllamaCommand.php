<?php

namespace App\Command;

use App\AI\LLM\OllamaClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ollama',
    description: 'Ask a question to Ollama LLM',
)]
class OllamaCommand extends Command
{
    public function __construct(
        private OllamaClient $ollamaClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'question',
                InputArgument::OPTIONAL,
                'The question or prompt to ask Ollama'
            )
            ->addOption(
                'model',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Model to use (default: llama2)',
                null
            )
            ->addOption(
                'temperature',
                't',
                InputOption::VALUE_OPTIONAL,
                'Temperature (0-1, higher = more creative)',
                0.7
            )
            ->addOption(
                'stream',
                's',
                InputOption::VALUE_NONE,
                'Stream output in real-time'
            )
            ->addOption(
                'list-models',
                null,
                InputOption::VALUE_NONE,
                'List available models'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln($this->ollamaClient->getMetaData());

        if ($input->getOption('list-models')) {
            return $this->listModels($io);
        }

        if (!$input->getArgument('question')) {
            $io->error('Question argument is required (unless using --list-models)');

            return Command::FAILURE;
        }

        if (!$this->ollamaClient->isAvailable()) {
            $io->error('❌ Ollama is not available');
            $io->note('Make sure Ollama is running: ollama serve');

            return Command::FAILURE;
        }

        $io->success('✓ Ollama is running');

        $model = $input->getOption('model');
        if (is_string($model)) {
            $this->ollamaClient->setModel($model);
        }

        $questionArg = $input->getArgument('question');
        $question = is_string($questionArg) ? $questionArg : '';
        $tempArg = $input->getOption('temperature');
        $temperature = is_string($tempArg) ? (float) $tempArg : 0.7;
        $stream = $input->getOption('stream');

        $io->section('Model: '.$this->ollamaClient->getModel());
        $io->writeln('<fg=cyan>Question:</> '.$question);
        $io->newLine();

        try {
            if ($stream) {
                return $this->askWithStreaming($io, $question, $temperature);
            }

            return $this->askWithoutStreaming($io, $question, $temperature);
        } catch (\Exception $e) {
            $io->error('Error communicating with Ollama: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function askWithoutStreaming(
        SymfonyStyle $io,
        string $question,
        float $temperature,
    ): int {
        $io->note('Waiting for response... (this may take a moment)');

        $response = $this->ollamaClient->ask($question, $temperature);

        $io->writeln('<fg=green>Response:</>'.PHP_EOL);
        $io->writeln($response);

        return Command::SUCCESS;
    }

    private function askWithStreaming(
        SymfonyStyle $io,
        string $question,
        float $temperature,
    ): int {
        $io->writeln('<fg=green>Response (streaming):</>'.PHP_EOL);

        try {
            $this->ollamaClient->askStream(
                $question,
                function (string $chunk) use ($io) {
                    $io->write($chunk);
                },
                $temperature
            );

            $io->newLine(2);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Streaming error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function listModels(SymfonyStyle $io): int
    {
        if (!$this->ollamaClient->isAvailable()) {
            $io->error('❌ Ollama is not available');

            return Command::FAILURE;
        }

        $models = $this->ollamaClient->getAvailableModels();

        if (empty($models)) {
            $io->warning('No models found. Pull a model first:');
            $io->writeln('  ollama pull llama2');

            return Command::SUCCESS;
        }

        $io->success('Available models:');
        $io->listing($models);

        return Command::SUCCESS;
    }
}
