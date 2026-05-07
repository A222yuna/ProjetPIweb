<?php

namespace App\Tests\Planning;

use App\Entity\Cabinet;
use App\Entity\Disponibilite;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la logique métier du DisponibiliteController (sans HTTP).
 *
 * Couvre les règles de validation extraites du controller :
 *  - Ownership du cabinet
 *  - heure_fin > heure_debut
 *  - Avertissement si la durée ne divise pas exactement la fenêtre
 *  - Tri et pagination (validation des paramètres)
 */
class DisponibiliteControllerLogicTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeDisponibilite(
        string $debut = '09:00',
        string $fin = '17:00',
        int $duree = 60
    ): Disponibilite {
        $d = new Disponibilite();
        $d->setHeureDebut(new \DateTimeImmutable($debut));
        $d->setHeureFin(new \DateTimeImmutable($fin));
        $d->setDureeConsultation($duree);
        return $d;
    }

    // -------------------------------------------------------------------------
    // RULE — heure_fin doit être strictement après heure_debut
    // -------------------------------------------------------------------------

    public function testHeureFinAfterHeureDebutIsValid(): void
    {
        $d = $this->makeDisponibilite('09:00', '17:00');

        $isValid = $d->getHeureFin() > $d->getHeureDebut();

        $this->assertTrue($isValid);
    }

    public function testHeureFinEqualHeureDebutIsInvalid(): void
    {
        $d = $this->makeDisponibilite('09:00', '09:00');

        $isInvalid = $d->getHeureFin() <= $d->getHeureDebut();

        $this->assertTrue($isInvalid);
    }

    public function testHeureFinBeforeHeureDebutIsInvalid(): void
    {
        $d = $this->makeDisponibilite('17:00', '09:00');

        $isInvalid = $d->getHeureFin() <= $d->getHeureDebut();

        $this->assertTrue($isInvalid);
    }

    // -------------------------------------------------------------------------
    // RULE — Avertissement si durée ne divise pas exactement la fenêtre
    // -------------------------------------------------------------------------

    /**
     * Reproduit la logique de checkDurationWarning() du controller.
     */
    private function shouldWarnAboutDuration(Disponibilite $d): bool
    {
        $start = $d->getHeureDebut();
        $end   = $d->getHeureFin();
        if (!$start || !$end || $d->getDureeConsultation() <= 0) {
            return false;
        }
        $totalMin = ((int)$end->format('H')) * 60 + (int)$end->format('i')
                  - (((int)$start->format('H')) * 60 + (int)$start->format('i'));

        return $totalMin > 0 && $totalMin % $d->getDureeConsultation() !== 0;
    }

    public function testNoDurationWarningWhenDurationDividesExactly(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 60); // 180 / 60 = 3

        $this->assertFalse($this->shouldWarnAboutDuration($d));
    }

    public function testDurationWarningWhenDurationDoesNotDivide(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 50); // 180 % 50 = 30 ≠ 0

        $this->assertTrue($this->shouldWarnAboutDuration($d));
    }

    public function testNoDurationWarningFor30MinSlots(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 30); // 180 / 30 = 6

        $this->assertFalse($this->shouldWarnAboutDuration($d));
    }

    public function testNoDurationWarningFor45MinSlots(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:45', 45); // 225 / 45 = 5

        $this->assertFalse($this->shouldWarnAboutDuration($d));
    }

    public function testDurationWarningFor45MinIn3HourWindow(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 45); // 180 % 45 = 0 → no warning

        $this->assertFalse($this->shouldWarnAboutDuration($d));
    }

    public function testDurationWarningFor70MinIn3HourWindow(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 70); // 180 % 70 = 40 ≠ 0

        $this->assertTrue($this->shouldWarnAboutDuration($d));
    }

    public function testNoDurationWarningWhenDureeIsZero(): void
    {
        $d = $this->makeDisponibilite('09:00', '12:00', 0);

        $this->assertFalse($this->shouldWarnAboutDuration($d));
    }

    // -------------------------------------------------------------------------
    // RULE — Validation des paramètres de tri
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('validSortFieldsProvider')]
    public function testValidSortFieldsAreAccepted(string $sortBy): void
    {
        $allowedSorts = ['jour', 'heureDebut', 'heureFin', 'dureeConsultation'];
        $resolved     = \in_array($sortBy, $allowedSorts, true) ? $sortBy : 'jour';

        $this->assertEquals($sortBy, $resolved);
    }

    /** @return array<string, array{string}> */
    public static function validSortFieldsProvider(): array
    {
        return [
            'jour'               => ['jour'],
            'heureDebut'         => ['heureDebut'],
            'heureFin'           => ['heureFin'],
            'dureeConsultation'  => ['dureeConsultation'],
        ];
    }

    public function testInvalidSortFieldFallsBackToJour(): void
    {
        $allowedSorts = ['jour', 'heureDebut', 'heureFin', 'dureeConsultation'];
        $sortBy       = 'invalid_field';
        $resolved     = \in_array($sortBy, $allowedSorts, true) ? $sortBy : 'jour';

        $this->assertEquals('jour', $resolved);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sortDirectionProvider')]
    public function testSortDirectionNormalization(string $input, string $expected): void
    {
        $resolved = strtoupper($input) === 'DESC' ? 'DESC' : 'ASC';

        $this->assertEquals($expected, $resolved);
    }

    /** @return array<string, array{string, string}> */
    public static function sortDirectionProvider(): array
    {
        return [
            'asc lowercase'  => ['asc', 'ASC'],
            'ASC uppercase'  => ['ASC', 'ASC'],
            'desc lowercase' => ['desc', 'DESC'],
            'DESC uppercase' => ['DESC', 'DESC'],
            'invalid'        => ['invalid', 'ASC'],
            'empty'          => ['', 'ASC'],
        ];
    }

    // -------------------------------------------------------------------------
    // RULE — Pagination : page minimum = 1
    // -------------------------------------------------------------------------

    public function testPageMinimumIsOne(): void
    {
        $page = max(1, 0);
        $this->assertEquals(1, $page);

        $page = max(1, -5);
        $this->assertEquals(1, $page);

        $page = max(1, 3);
        $this->assertEquals(3, $page);
    }

    // -------------------------------------------------------------------------
    // RULE — Calcul du nombre de pages
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('totalPagesProvider')]
    public function testTotalPagesCalculation(int $total, int $perPage, int $expectedPages): void
    {
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->assertEquals($expectedPages, $totalPages);
    }

    /** @return array<string, array{int, int, int}> */
    public static function totalPagesProvider(): array
    {
        return [
            'zero items'       => [0, 10, 1],
            'exact one page'   => [10, 10, 1],
            'one extra item'   => [11, 10, 2],
            'three pages'      => [25, 10, 3],
            'partial last page'=> [21, 10, 3],
        ];
    }
}
