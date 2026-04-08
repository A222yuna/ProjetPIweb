<?php

namespace App\Form;

use App\Entity\PsychologuePlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

final class PsychologuePlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayOfWeek', ChoiceType::class, [
                'choices' => [
                    'Lundi' => 'MONDAY',
                    'Mardi' => 'TUESDAY',
                    'Mercredi' => 'WEDNESDAY',
                    'Jeudi' => 'THURSDAY',
                    'Vendredi' => 'FRIDAY',
                    'Samedi' => 'SATURDAY',
                    'Dimanche' => 'SUNDAY',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le jour est obligatoire'),
                    new Choice(choices: PsychologuePlan::DAY_OF_WEEK_CHOICES),
                ],
            ])
            ->add('period', ChoiceType::class, [
                'choices' => ['Jour' => 'DAY', 'Nuit' => 'NIGHT'],
                'constraints' => [
                    new NotBlank(message: 'La période est obligatoire'),
                    new Choice(choices: PsychologuePlan::PERIOD_CHOICES),
                ],
            ])
            ->add('maxAppointments', IntegerType::class, [
                'constraints' => [
                    new NotBlank(message: 'Le nombre max est obligatoire'),
                    new Positive(message: 'Doit être un nombre positif'),
                    new Range(min: 1, max: 20, notInRangeMessage: 'Entre 1 et 20 rendez-vous maximum'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PsychologuePlan::class]);
    }
}
