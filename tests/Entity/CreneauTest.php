<?php

namespace App\Tests\Entity;

use App\Entity\Creneau;
use App\Entity\Disponibilite;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CreneauTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction & valeurs par défaut
    // -------------------------------------------------------------------------

    public function testDefaultStatutIsReserve(): void
    {
        $creneau = new Creneau();
        $this->assertEquals(Creneau::STATUT_RESERVE, $creneau->getStatut());
    }

    public function testIdIsNullByDefault(): void
    {
        $creneau = new Creneau();
        $this->assertNull($creneau->getId());
    }

    // -------------------------------------------------------------------------
    // Setters / Getters
    // -------------------------------------------------------------------------

    public function testSetAndGetPatient(): void
    {
        $creneau = new Creneau();
        $patient = new User();
        $patient->setNom('Dupont');
        $patient->setPrenom('Jean');

        $creneau->setPatient($patient);

        $this->assertSame($patient, $creneau->getPatient());
    }

    public function testSetAndGetDisponibilite(): void
    {
        $creneau = new Creneau();
        $dispo   = new Disponibilite();

        $creneau->setDisponibilite($dispo);

        $this->assertSame($dispo, $creneau->getDisponibilite());
    }

    public function testSetAndGetDateCreneau(): void
    {
        $creneau = new Creneau();
        $date    = new \DateTimeImmutable('2026-06-15');

        $creneau->setDateCreneau($date);

        $this->assertSame($date, $creneau->getDateCreneau());
        $this->assertEquals('2026-06-15', $creneau->getDateCreneau()->format('Y-m-d'));
    }

    public function testSetAndGetHeure(): void
    {
        $creneau = new Creneau();
        $heure   = new \DateTimeImmutable('10:30:00');

        $creneau->setHeure($heure);

        $this->assertSame($heure, $creneau->getHeure());
        $this->assertEquals('10:30', $creneau->getHeure()->format('H:i'));
    }

    public function testSetStatutToAnnule(): void
    {
        $creneau = new Creneau();
        $creneau->setStatut(Creneau::STATUT_ANNULE);

        $this->assertEquals(Creneau::STATUT_ANNULE, $creneau->getStatut());
    }

    public function testSetStatutBackToReserve(): void
    {
        $creneau = new Creneau();
        $creneau->setStatut(Creneau::STATUT_ANNULE);
        $creneau->setStatut(Creneau::STATUT_RESERVE);

        $this->assertEquals(Creneau::STATUT_RESERVE, $creneau->getStatut());
    }

    // -------------------------------------------------------------------------
    // Constantes
    // -------------------------------------------------------------------------

    public function testStatutConstants(): void
    {
        $this->assertEquals('RESERVE', Creneau::STATUT_RESERVE);
        $this->assertEquals('ANNULE', Creneau::STATUT_ANNULE);
    }

    // -------------------------------------------------------------------------
    // Fluent interface (méthodes retournent static)
    // -------------------------------------------------------------------------

    public function testFluentInterface(): void
    {
        $creneau = new Creneau();
        $patient = new User();
        $dispo   = new Disponibilite();
        $date    = new \DateTimeImmutable('2026-07-01');
        $heure   = new \DateTimeImmutable('09:00:00');

        $result = $creneau
            ->setPatient($patient)
            ->setDisponibilite($dispo)
            ->setDateCreneau($date)
            ->setHeure($heure)
            ->setStatut(Creneau::STATUT_RESERVE);

        $this->assertSame($creneau, $result);
    }

    // -------------------------------------------------------------------------
    // Scénario complet
    // -------------------------------------------------------------------------

    public function testFullCreneauScenario(): void
    {
        $patient = new User();
        $patient->setNom('Martin');
        $patient->setPrenom('Sophie');
        $patient->setEmail('sophie.martin@example.com');

        $dispo = new Disponibilite();
        $dispo->setJour(1); // Lundi
        $dispo->setHeureDebut(new \DateTimeImmutable('09:00'));
        $dispo->setHeureFin(new \DateTimeImmutable('17:00'));
        $dispo->setDureeConsultation(60);

        $creneau = new Creneau();
        $creneau->setPatient($patient);
        $creneau->setDisponibilite($dispo);
        $creneau->setDateCreneau(new \DateTimeImmutable('2026-06-22'));
        $creneau->setHeure(new \DateTimeImmutable('10:00'));

        $this->assertEquals('Martin', $creneau->getPatient()->getNom());
        $this->assertEquals(1, $creneau->getDisponibilite()->getJour());
        $this->assertEquals('2026-06-22', $creneau->getDateCreneau()->format('Y-m-d'));
        $this->assertEquals('10:00', $creneau->getHeure()->format('H:i'));
        $this->assertEquals(Creneau::STATUT_RESERVE, $creneau->getStatut());
    }
}
