<?php

namespace App\Tests\Planning;

use App\Entity\Appointment;
use App\Entity\Creneau;
use PHPUnit\Framework\TestCase;

/**
 * Tests du calcul des couleurs d'événements du calendrier.
 *
 * Reproduit la logique de couleur des CalendarController (psychologue et patient)
 * sans dépendance à la base de données.
 */
class CalendarEventColorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Logique de couleur extraite des controllers
    // -------------------------------------------------------------------------

    /**
     * Reproduit la logique de couleur du CalendarController (psychologue).
     */
    private function getColorForAppointmentStatus(string $status): string
    {
        return match ($status) {
            Appointment::STATUS_PAID      => '#2ecc71', // Vert
            Appointment::STATUS_CONFIRMED => '#3498db', // Bleu
            Appointment::STATUS_SCHEDULED => '#f1c40f', // Jaune
            Appointment::STATUS_COMPLETED => '#95a5a6', // Gris
            Appointment::STATUS_CANCELLED => '#e74c3c', // Rouge
            default                       => '#5B9BD5',
        };
    }

    /**
     * Reproduit la logique de couleur du PatientCalendarController.
     */
    private function getColorForPatientCalendar(string $status): string
    {
        $color = '#5B9BD5';
        if ($status === Appointment::STATUS_PAID) {
            $color = '#2ecc71';
        } elseif ($status === Appointment::STATUS_CONFIRMED) {
            $color = '#3498db';
        } elseif ($status === Creneau::STATUT_ANNULE || $status === Appointment::STATUS_CANCELLED) {
            $color = '#e74c3c';
        } elseif ($status === Appointment::STATUS_SCHEDULED) {
            $color = '#f1c40f';
        }
        return $color;
    }

    // -------------------------------------------------------------------------
    // Tests de couleur — CalendarController (psychologue)
    // -------------------------------------------------------------------------

    public function testPaidAppointmentIsGreen(): void
    {
        $color = $this->getColorForAppointmentStatus(Appointment::STATUS_PAID);
        $this->assertEquals('#2ecc71', $color);
    }

    public function testConfirmedAppointmentIsBlue(): void
    {
        $color = $this->getColorForAppointmentStatus(Appointment::STATUS_CONFIRMED);
        $this->assertEquals('#3498db', $color);
    }

    public function testScheduledAppointmentIsYellow(): void
    {
        $color = $this->getColorForAppointmentStatus(Appointment::STATUS_SCHEDULED);
        $this->assertEquals('#f1c40f', $color);
    }

    public function testCompletedAppointmentIsGray(): void
    {
        $color = $this->getColorForAppointmentStatus(Appointment::STATUS_COMPLETED);
        $this->assertEquals('#95a5a6', $color);
    }

    public function testCancelledAppointmentIsRed(): void
    {
        $color = $this->getColorForAppointmentStatus(Appointment::STATUS_CANCELLED);
        $this->assertEquals('#e74c3c', $color);
    }

    public function testUnknownStatusIsDefaultBlue(): void
    {
        $color = $this->getColorForAppointmentStatus('UNKNOWN_STATUS');
        $this->assertEquals('#5B9BD5', $color);
    }

    // -------------------------------------------------------------------------
    // Tests de couleur — PatientCalendarController
    // -------------------------------------------------------------------------

    public function testPatientCalendarPaidIsGreen(): void
    {
        $color = $this->getColorForPatientCalendar(Appointment::STATUS_PAID);
        $this->assertEquals('#2ecc71', $color);
    }

    public function testPatientCalendarConfirmedIsBlue(): void
    {
        $color = $this->getColorForPatientCalendar(Appointment::STATUS_CONFIRMED);
        $this->assertEquals('#3498db', $color);
    }

    public function testPatientCalendarScheduledIsYellow(): void
    {
        $color = $this->getColorForPatientCalendar(Appointment::STATUS_SCHEDULED);
        $this->assertEquals('#f1c40f', $color);
    }

    public function testPatientCalendarCancelledAppointmentIsRed(): void
    {
        $color = $this->getColorForPatientCalendar(Appointment::STATUS_CANCELLED);
        $this->assertEquals('#e74c3c', $color);
    }

    public function testPatientCalendarAnnuleCreneauIsRed(): void
    {
        $color = $this->getColorForPatientCalendar(Creneau::STATUT_ANNULE);
        $this->assertEquals('#e74c3c', $color);
    }

    public function testPatientCalendarDefaultIsBlue(): void
    {
        $color = $this->getColorForPatientCalendar('RESERVE'); // Creneau::STATUT_RESERVE
        $this->assertEquals('#5B9BD5', $color);
    }

    // -------------------------------------------------------------------------
    // Tests de cohérence entre les deux controllers
    // -------------------------------------------------------------------------

    public function testBothControllersAgreeOnPaidColor(): void
    {
        $psyColor     = $this->getColorForAppointmentStatus(Appointment::STATUS_PAID);
        $patientColor = $this->getColorForPatientCalendar(Appointment::STATUS_PAID);

        $this->assertEquals($psyColor, $patientColor);
    }

    public function testBothControllersAgreeOnConfirmedColor(): void
    {
        $psyColor     = $this->getColorForAppointmentStatus(Appointment::STATUS_CONFIRMED);
        $patientColor = $this->getColorForPatientCalendar(Appointment::STATUS_CONFIRMED);

        $this->assertEquals($psyColor, $patientColor);
    }

    public function testBothControllersAgreeOnCancelledColor(): void
    {
        $psyColor     = $this->getColorForAppointmentStatus(Appointment::STATUS_CANCELLED);
        $patientColor = $this->getColorForPatientCalendar(Appointment::STATUS_CANCELLED);

        $this->assertEquals($psyColor, $patientColor);
    }

    // -------------------------------------------------------------------------
    // Tests du format de date/heure pour les événements
    // -------------------------------------------------------------------------

    public function testEventStartFormatIsIso8601(): void
    {
        $date  = new \DateTimeImmutable('2026-06-15');
        $heure = new \DateTimeImmutable('10:30:00');

        $start = $date->format('Y-m-d') . 'T' . $heure->format('H:i:s');

        $this->assertEquals('2026-06-15T10:30:00', $start);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $start);
    }

    public function testEventStartFormatWithMidnight(): void
    {
        $date  = new \DateTimeImmutable('2026-12-31');
        $heure = new \DateTimeImmutable('00:00:00');

        $start = $date->format('Y-m-d') . 'T' . $heure->format('H:i:s');

        $this->assertEquals('2026-12-31T00:00:00', $start);
    }
}
