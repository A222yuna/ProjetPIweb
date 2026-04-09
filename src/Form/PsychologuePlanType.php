<?php

namespace App\Form;

use App\Entity\PsychologuePlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                'constraints' => [new NotBlank()],
            ])
            ->add('period', ChoiceType::class, [
                'choices' => ['Jour' => 'DAY', 'Nuit' => 'NIGHT'],
                'constraints' => [new NotBlank()],
            ])
            ->add('maxAppointments', IntegerType::class, [
                'constraints' => [new GreaterThanOrEqual(1)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PsychologuePlan::class]);
    }
}
