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
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

final class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cabinet', EntityType::class, [
                'class' => Cabinet::class,
                'choice_label' => static fn (Cabinet $c) => sprintf('%s - %s', $c->getVille(), $c->getAdresse()),
                'constraints' => [new NotBlank()],
            ])
            ->add('jour', ChoiceType::class, [
                'choices' => [
                    'Lundi' => 1, 'Mardi' => 2, 'Mercredi' => 3, 'Jeudi' => 4, 'Vendredi' => 5, 'Samedi' => 6, 'Dimanche' => 0,
                ],
            ])
            ->add('heureDebut', TimeType::class, ['input' => 'datetime'])
            ->add('heureFin', TimeType::class, ['input' => 'datetime'])
            ->add('dureeConsultation', IntegerType::class, [
                'constraints' => [new GreaterThanOrEqual(10)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Disponibilite::class]);
    }
}
