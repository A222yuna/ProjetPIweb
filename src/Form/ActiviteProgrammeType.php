<?php

namespace App\Form;

use App\Entity\ActiviteProgramme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActiviteProgrammeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'activité',
                'attr' => ['placeholder' => 'Ex : Méditation guidée'],
            ])
            ->add('jour', IntegerType::class, [
                'label' => 'Jour',
                'attr' => ['placeholder' => 'Ex : 1', 'min' => 1],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Détails de l\'activité...', 'rows' => 3],
            ])
            ->add('dureeMinutes', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 45', 'min' => 1],
            ])
            ->add('typeActivite', ChoiceType::class, [
                'label' => 'Type d\'activité',
                'choices' => [
                    'Méditation' => 'meditation',
                    'Exercice physique' => 'exercice_physique',
                    'Respiration' => 'respiration',
                    'Lecture' => 'lecture',
                    'Écriture' => 'ecriture',
                    'Discussion' => 'discussion',
                    'Autre' => 'autre',
                ],
                'placeholder' => '-- Choisir un type --',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActiviteProgramme::class,
        ]);
    }
}
