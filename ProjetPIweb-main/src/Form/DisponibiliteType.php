<?php

namespace App\Form;

use App\Entity\Cabinet;
use App\Entity\Disponibilite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cabinet', EntityType::class, [
                'class' => Cabinet::class,
                'choice_label' => static fn (Cabinet $c) => sprintf('%s - %s', $c->getVille(), $c->getAdresse()),
                'placeholder' => 'Choisir un cabinet',
                'label' => 'Cabinet',
                'required' => false,
            ])
            ->add('jour', ChoiceType::class, [
                'choices' => [
                    'Lundi' => 1,
                    'Mardi' => 2,
                    'Mercredi' => 3,
                    'Jeudi' => 4,
                    'Vendredi' => 5,
                    'Samedi' => 6,
                    'Dimanche' => 7,
                ],
                'label' => 'Jour',
                'required' => false,
            ])
            ->add('heureDebut', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'input_format' => 'H:i',
                'with_seconds' => false,
                'label' => 'Heure debut',
                'invalid_message' => 'Le format attendu est HH:mm.',
                'required' => false,
            ])
            ->add('heureFin', TimeType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'input_format' => 'H:i',
                'with_seconds' => false,
                'label' => 'Heure fin',
                'invalid_message' => 'Le format attendu est HH:mm.',
                'required' => false,
            ])
            ->add('dureeConsultation', IntegerType::class, [
                'label' => 'Duree consultation (minutes)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Disponibilite::class]);
    }
}
