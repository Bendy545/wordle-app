<?php

namespace App\Command;

use App\Service\WordRotationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:rotate-word',description: 'Rotates word at fixed times: 08, 10, 12, 14, 16',)]
class RotateWordCommand extends Command
{
    public function __construct(private WordRotationService $rotationService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->rotationService->rotateIfNeeded();
        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $msg = $result['changed']
            ? ($result['initialized'] ? 'Initialized' : 'Rotated')
            : 'No change';

        $output->writeln(sprintf(
            '%s. Slot %s (%s). Word: %s',
            $msg,
            $result['slot'],
            $result['date']->format('Y-m-d'),
            $result['word'] ?? 'NULL'
        ));

        return Command::SUCCESS;
    }
}
