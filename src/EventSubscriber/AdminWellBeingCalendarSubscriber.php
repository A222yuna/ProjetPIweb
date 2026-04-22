<?php

namespace App\EventSubscriber;

use App\Entity\ActiviteProgramme;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AdminWellBeingCalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $filters = $calendar->getFilters();
        if (($filters['admin_wellbeing'] ?? false) !== true) {
            return;
        }

        $queryBuilder = $this->entityManager->getRepository(ActiviteProgramme::class)->createQueryBuilder('a')
            ->leftJoin('a.programme', 'p')->addSelect('p')
            ->orderBy('a.jour', 'ASC')
            ->addOrderBy('a.heureDebut', 'ASC');

        $programmeId = (int) ($filters['programme_id'] ?? 0);
        if ($programmeId > 0) {
            $queryBuilder->andWhere('p.id = :programmeId')
                ->setParameter('programmeId', $programmeId);
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if ($type !== '') {
            $queryBuilder->andWhere('a.typeActivite = :type')
                ->setParameter('type', $type);
        }

        $jour = (int) ($filters['jour'] ?? 0);
        if ($jour > 0) {
            $queryBuilder->andWhere('a.jour = :jour')
                ->setParameter('jour', $jour);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $queryBuilder->andWhere('LOWER(a.titre) LIKE :q OR LOWER(a.description) LIKE :q OR LOWER(p.nom) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $weekStart = (new \DateTimeImmutable('monday this week'))->setTime(0, 0);
        $activities = $queryBuilder->getQuery()->getResult();

        foreach ($activities as $activity) {
            $dayOffset = max(0, $activity->getJour() - 1);
            $time = $activity->getHeureDebut();
            $hour = (int) ($time?->format('H') ?? 8);
            $minute = (int) ($time?->format('i') ?? 0);
            $duration = max(15, (int) ($activity->getDureeMinutes() ?? 30));

            $start = $weekStart->modify(sprintf('+%d days', $dayOffset))->setTime($hour, $minute);
            $end = $start->modify(sprintf('+%d minutes', $duration));

            $programmeName = $activity->getProgramme()?->getNom() ?? 'Programme';
            $title = sprintf('%s - %s', $programmeName, $activity->getTitre() ?? 'Activite');
            $event = new Event($title, $start, $end);

            $event->addOption('backgroundColor', '#7c6ba0');
            $event->addOption('borderColor', '#7c6ba0');
            $event->addOption('textColor', '#ffffff');
            $event->addOption('extendedProps', [
                'programme' => $programmeName,
                'type' => $activity->getTypeActivite() ?? 'N/A',
                'jour' => $activity->getJour(),
            ]);

            $calendar->addEvent($event);
        }
    }
}
