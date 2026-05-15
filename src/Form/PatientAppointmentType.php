<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\PsychologuePlan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PatientAppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plan', EntityType::class, [
            'class' => PsychologuePlan::class,
            'choice_label' => static fn (PsychologuePlan $p) => sprintf(
                'Dr. %s %s — %s %s (max %d)',
                $p->getPsychologue()?->getPrenom() ?? '',
                $p->getPsychologue()?->getNom() ?? '',
                $p->getDayOfWeek(),
                $p->getPeriod(),
                $p->getMaxAppointments()
            ),
            'placeholder' => 'Choisir un psychologue et un créneau...',
            'constraints' => [new NotBlank(message: 'Veuillez choisir un planning')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Appointment::class]);
    }
}
