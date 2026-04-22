<?php

namespace App\Command;

use App\Service\ReputationCalculatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cabinet:update-reputation',
    description: 'Recalcule et persiste les scores de réputation de tous les cabinets',
)]
class UpdateReputationScoresCommand extends Command
{
    public function __construct(private ReputationCalculatorService $reputationCalculator)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des Scores de Réputation');

        $results = $this->reputationCalculator->updateAll();

        $io->table(
            ['Cabinet ID', 'Ville', 'Score', 'Badge'],
            array_map(fn($r) => [
                $r['cabinet_id'],
                $r['ville'],
                $r['score'] . '/100',
                $r['badge'],
            ], $results)
        );

        $io->success(count($results) . ' cabinet(s) mis à jour.');
        return Command::SUCCESS;
    }
}
