<?php

namespace App\Tests\Entity;

use App\Entity\Cabinet;
use App\Entity\Disponibilite;
use PHPUnit\Framework\TestCase;

class DisponibiliteTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction & valeurs par défaut
    // -------------------------------------------------------------------------

    public function testIdIsNullByDefault(): void
    {
        $dispo = new Disponibilite();
        $this->assertNull($dispo->getId());
    }

    public function testDefaultJourIsZero(): void
    {
        $dispo = new Disponibilite();
        $this->assertEquals(0, $dispo->getJour());
    }

    public function testDefaultDureeIsZero(): void
    {
        $dispo = new Disponibilite();
        $this->assertEquals(0, $dispo->getDureeConsultation());
    }

    public function testCreneauxCollectionIsEmptyByDefault(): void
    {
        $dispo = new Disponibilite();
        $this->assertCount(0, $dispo->getCreneaux());
    }

    // -------------------------------------------------------------------------
    // Setters / Getters
    // -------------------------------------------------------------------------

    public function testSetAndGetCabinet(): void
    {
        $dispo   = new Disponibilite();
        $cabinet = new Cabinet();
        $cabinet->setVille('Lyon');

        $dispo->setCabinet($cabinet);

        $this->assertSame($cabinet, $dispo->getCabinet());
        $this->assertEquals('Lyon', $dispo->getCabinet()->getVille());
    }

    public function testSetAndGetJour(): void
    {
        $dispo = new Disponibilite();
        $dispo->setJour(3); // Mercredi

        $this->assertEquals(3, $dispo->getJour());
    }

    public function testSetAndGetHeureDebut(): void
    {
        $dispo = new Disponibilite();
        $heure = new \DateTimeImmutable('08:30');

        $dispo->setHeureDebut($heure);

        $this->assertSame($heure, $dispo->getHeureDebut());
        $this->assertEquals('08:30', $dispo->getHeureDebut()->format('H:i'));
    }

    public function testSetAndGetHeureFin(): void
    {
        $dispo = new Disponibilite();
        $heure = new \DateTimeImmutable('17:00');

        $dispo->setHeureFin($heure);

        $this->assertSame($heure, $dispo->getHeureFin());
        $this->assertEquals('17:00', $dispo->getHeureFin()->format('H:i'));
    }

    public function testSetAndGetDureeConsultation(): void
    {
        $dispo = new Disponibilite();
        $dispo->setDureeConsultation(45);

        $this->assertEquals(45, $dispo->getDureeConsultation());
    }

    // -------------------------------------------------------------------------
    // Règles métier : heure_fin > heure_debut
    // -------------------------------------------------------------------------

    public function testHeureFinAfterHeureDebut(): void
    {
        $dispo = new Disponibilite();
        $debut = new \DateTimeImmutable('09:00');
        $fin   = new \DateTimeImmutable('17:00');

        $dispo->setHeureDebut($debut);
        $dispo->setHeureFin($fin);

        $this->assertTrue($dispo->getHeureFin() > $dispo->getHeureDebut());
    }

    public function testHeureFinEqualHeureDebutIsInvalid(): void
    {
        $dispo = new Disponibilite();
        $heure = new \DateTimeImmutable('09:00');

        $dispo->setHeureDebut($heure);
        $dispo->setHeureFin($heure);

        // L'entité ne lève pas d'exception, c'est le controller qui valide
        // On vérifie que la condition de validation échouerait
        $this->assertFalse($dispo->getHeureFin() > $dispo->getHeureDebut());
    }

    public function testHeureFinBeforeHeureDebutIsInvalid(): void
    {
        $dispo = new Disponibilite();
        $dispo->setHeureDebut(new \DateTimeImmutable('17:00'));
        $dispo->setHeureFin(new \DateTimeImmutable('09:00'));

        $this->assertTrue($dispo->getHeureFin() <= $dispo->getHeureDebut());
    }

    // -------------------------------------------------------------------------
    // Calcul du nombre de créneaux possibles
    // -------------------------------------------------------------------------

    public function testSlotCountCalculation(): void
    {
        $dispo = new Disponibilite();
        $dispo->setHeureDebut(new \DateTimeImmutable('09:00'));
        $dispo->setHeureFin(new \DateTimeImmutable('12:00'));
        $dispo->setDureeConsultation(60);

        $debut    = $dispo->getHeureDebut();
        $fin      = $dispo->getHeureFin();
        $duree    = $dispo->getDureeConsultation();
        $totalMin = ((int)$fin->format('H') * 60 + (int)$fin->format('i'))
                  - ((int)$debut->format('H') * 60 + (int)$debut->format('i'));

        $this->assertEquals(180, $totalMin);
        $this->assertEquals(3, intdiv($totalMin, $duree));
    }

    public function testDurationDividesWindowExactly(): void
    {
        $dispo = new Disponibilite();
        $dispo->setHeureDebut(new \DateTimeImmutable('09:00'));
        $dispo->setHeureFin(new \DateTimeImmutable('12:00'));
        $dispo->setDureeConsultation(60);

        $debut    = $dispo->getHeureDebut();
        $fin      = $dispo->getHeureFin();
        $duree    = $dispo->getDureeConsultation();
        $totalMin = ((int)$fin->format('H') * 60 + (int)$fin->format('i'))
                  - ((int)$debut->format('H') * 60 + (int)$debut->format('i'));

        $this->assertEquals(0, $totalMin % $duree, 'La durée doit diviser exactement la fenêtre');
    }

    public function testDurationDoesNotDivideWindowExactly(): void
    {
        $dispo = new Disponibilite();
        $dispo->setHeureDebut(new \DateTimeImmutable('09:00'));
        $dispo->setHeureFin(new \DateTimeImmutable('12:00'));
        $dispo->setDureeConsultation(45); // 180 / 45 = 4 exact, let's use 50

        $dispo->setDureeConsultation(50);
        $debut    = $dispo->getHeureDebut();
        $fin      = $dispo->getHeureFin();
        $duree    = $dispo->getDureeConsultation();
        $totalMin = ((int)$fin->format('H') * 60 + (int)$fin->format('i'))
                  - ((int)$debut->format('H') * 60 + (int)$debut->format('i'));

        $this->assertNotEquals(0, $totalMin % $duree, 'Un warning devrait être émis');
    }

    // -------------------------------------------------------------------------
    // Jours valides (1 = Lundi … 7 = Dimanche)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('validJoursProvider')]
    public function testValidJours(int $jour): void
    {
        $dispo = new Disponibilite();
        $dispo->setJour($jour);

        $this->assertGreaterThanOrEqual(1, $dispo->getJour());
        $this->assertLessThanOrEqual(7, $dispo->getJour());
    }

    /** @return array<array{int}> */
    public static function validJoursProvider(): array
    {
        return [[1], [2], [3], [4], [5], [6], [7]];
    }

    // -------------------------------------------------------------------------
    // Durées valides (15–120 min)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('validDureesProvider')]
    public function testValidDurees(int $duree): void
    {
        $dispo = new Disponibilite();
        $dispo->setDureeConsultation($duree);

        $this->assertGreaterThanOrEqual(15, $dispo->getDureeConsultation());
        $this->assertLessThanOrEqual(120, $dispo->getDureeConsultation());
    }

    /** @return array<array{int}> */
    public static function validDureesProvider(): array
    {
        return [[15], [30], [45], [60], [90], [120]];
    }

    // -------------------------------------------------------------------------
    // Fluent interface
    // -------------------------------------------------------------------------

    public function testFluentInterface(): void
    {
        $dispo   = new Disponibilite();
        $cabinet = new Cabinet();

        $result = $dispo
            ->setCabinet($cabinet)
            ->setJour(2)
            ->setHeureDebut(new \DateTimeImmutable('09:00'))
            ->setHeureFin(new \DateTimeImmutable('18:00'))
            ->setDureeConsultation(30);

        $this->assertSame($dispo, $result);
    }
}
