<?php

namespace App\Form;

use App\Entity\Cabinet;
use App\Entity\Disponibilite;
use App\Entity\User;
use App\Repository\CabinetRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

final class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cabinet', EntityType::class, [
                'class' => Cabinet::class,
                'choice_label' => static fn (Cabinet $c) => sprintf('%s - %s', $c->getVille(), $c->getAdresse()),
                'query_builder' => function (CabinetRepository $repo) use ($options) {
                    $qb = $repo->createQueryBuilder('c')
                        ->orderBy('c.ville', 'ASC')
                        ->addOrderBy('c.adresse', 'ASC');
                    $psy = $options['psychologue_user'];
                    if ($psy instanceof User) {
                        $qb->leftJoin('c.psyCabinets', 'pc')
                            ->andWhere('pc.psychologue = :psy')
                            ->setParameter('psy', $psy);
                    }

                    return $qb;
                },
                'constraints' => [new NotBlank(message: 'Le cabinet est obligatoire')],
            ])
            ->add('jour', ChoiceType::class, [
                'choices' => [
                    'Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4, 'Vendredi' => 5, 'Samedi' => 6, 'Dimanche' => 7,
                ],
                'constraints' => [
                    new NotBlank(message: 'Le jour est obligatoire'),
                    new Range(min: 1, max: 7, notInRangeMessage: 'Jour invalide'),
                ],
            ])
            ->add('heureDebut', TimeType::class, [
                'input' => 'datetime_immutable',
                'constraints' => [new NotBlank(message: "L'heure de début est obligatoire")],
            ])
            ->add('heureFin', TimeType::class, [
                'input' => 'datetime_immutable',
                'constraints' => [new NotBlank(message: "L'heure de fin est obligatoire")],
            ])
            ->add('dureeConsultation', IntegerType::class, [
                'constraints' => [
                    new NotBlank(message: 'La durée est obligatoire'),
                    new Positive(message: 'La durée doit être positive'),
                    new Range(min: 15, max: 120, notInRangeMessage: 'La durée doit être entre 15 et 120 minutes'),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            /** @var Disponibilite $data */
            $data = $event->getData();
            $form = $event->getForm();
            $start = $data?->getHeureDebut();
            $end = $data?->getHeureFin();
            if ($start && $end && $end <= $start) {
                $form->get('heureFin')->addError(new \Symfony\Component\Form\FormError("L'heure de fin doit être après l'heure de début"));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
            'psychologue_user' => null,
        ]);
        $resolver->setAllowedTypes('psychologue_user', ['null', User::class]);
    }
}
