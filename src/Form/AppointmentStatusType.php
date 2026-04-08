<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AppointmentStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('status', ChoiceType::class, [
            'choices' => [
                'SCHEDULED' => Appointment::STATUS_SCHEDULED,
                'CANCELLED' => Appointment::STATUS_CANCELLED,
                'COMPLETED' => Appointment::STATUS_COMPLETED,
            ],
            'constraints' => [
                new NotBlank(message: 'Le statut est obligatoire'),
                new Choice(choices: [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED], message: 'Statut invalide'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'csrf_protection' => false,
        ]);
    }
}

