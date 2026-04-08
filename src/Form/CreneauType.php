<?php

namespace App\Form;

use App\Entity\Creneau;
use App\Entity\Disponibilite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CreneauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disponibilite', EntityType::class, [
                'class' => Disponibilite::class,
                'choice_label' => static function (Disponibilite $d): string {
                    return sprintf(
                        '#%d - %s %s-%s (%d min)',
                        $d->getId() ?? 0,
                        $d->getCabinet()?->getVille() ?? 'Cabinet',
                        $d->getHeureDebut()?->format('H:i') ?? '--:--',
                        $d->getHeureFin()?->format('H:i') ?? '--:--',
                        $d->getDureeConsultation()
                    );
                },
                'placeholder' => 'Choisir…',
                'constraints' => [new NotBlank(message: 'Veuillez choisir une disponibilité')],
            ])
            ->add('dateCreneau', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(message: 'La date est obligatoire'),
                    new GreaterThanOrEqual(value: 'today', message: 'La date ne peut pas être dans le passé'),
                ],
            ])
            ->add('heure', TimeType::class, [
                'widget' => 'single_text',
                'constraints' => [new NotBlank(message: "L'heure est obligatoire")],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Creneau::class]);
    }
}

