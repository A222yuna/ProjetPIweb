<?php

namespace App\Service;

use App\Entity\ActiviteProgramme;

class GoogleCalendarService
{
    /**
     * Generates a Google Calendar link for a specific activity.
     */
    public function generateLink(ActiviteProgramme $activite): string
    {
        $title = sprintf('[MindCare] %s - %s', 
            $activite->getProgramme()?->getNom() ?? 'Programme',
            $activite->getTitre() ?? 'Activité'
        );

        $description = $activite->getDescription() ?? 'Pas de description';
        $description .= "\n\nType: " . ($activite->getTypeActivite() ?? 'N/A');
        $description .= "\nJour du programme: J" . $activite->getJour();

        // Calculate dates
        // Since we don't have a specific start date, we use current date + day offset
        $startDate = new \DateTime();
        $startDate->modify('+' . ($activite->getJour() - 1) . ' days');
        
        if ($activite->getHeureDebut()) {
            $startDate->setTime(
                (int) $activite->getHeureDebut()->format('H'),
                (int) $activite->getHeureDebut()->format('i')
            );
        }

        $endDate = clone $startDate;
        $endDate->modify('+' . ($activite->getDureeMinutes() ?? 30) . ' minutes');

        // Format dates for Google: YYYYMMDDTHHMMSS
        // Note: Using local time without 'Z' so Google handles it relative to the user's timezone settings
        $dates = $startDate->format('Ymd\THis') . '/' . $endDate->format('Ymd\THis');

        $baseUrl = 'https://calendar.google.com/calendar/render';
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'details' => $description,
            'dates' => $dates,
            'ctz' => date_default_timezone_get(), // Pass the server timezone to help Google
        ];

        return $baseUrl . '?' . http_build_query($params);
    }
}
