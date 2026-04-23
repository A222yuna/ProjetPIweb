<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-notifications',
    description: 'Test email and SMS sending',
)]
class TestNotificationCommand extends Command
{
    public function __construct(private NotificationService $notifService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test Email + SMS');

        // Test Email
        $io->section('📧 Test Email → ghazelmaram18@gmail.com');
        try {
            $this->notifService->sendEmail(
                '🧪 Test Email — Cabinet Psychologie',
                'emails/rdv_confirmation.html.twig',
                [
                    'patient' => new class {
                        public function getPrenom(): string { return 'Maram'; }
                        public function getNom(): string { return 'Ghazel'; }
                    },
                    'date'  => date('d/m/Y'),
                    'heure' => date('H:i'),
                ]
            );
            $io->success('Email envoyé avec succès à ghazelmaram18@gmail.com');
        } catch (\Throwable $e) {
            $io->error('Email échoué : ' . $e->getMessage());
        }

        // Test SMS
        $io->section('📱 Test SMS → +21699076402');
        try {
            $this->notifService->sendSms(
                '[Cabinet Psy] Test SMS — Système de notifications opérationnel. ' . date('d/m/Y H:i')
            );
            $io->success('SMS envoyé avec succès au +21699076402');
        } catch (\Throwable $e) {
            $io->error('SMS échoué : ' . $e->getMessage());
            $io->note('Vérifiez TWILIO_DSN dans .env');
        }

        return Command::SUCCESS;
    }
}
