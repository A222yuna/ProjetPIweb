<?php

namespace App\Form;

use App\Entity\ActiviteProgramme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ActiviteProgrammeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'constraints' => [new NotBlank(message: 'Le titre est obligatoire')],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('jour', IntegerType::class, [
                'label' => 'Jour du programme (ex: 1)',
                'constraints' => [
                    new NotBlank(message: 'Le jour est obligatoire'),
                    new Positive(message: 'Le jour doit être positif'),
                ],
            ])
            ->add('heureDebut', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de début',
                'constraints' => [new NotBlank(message: 'L\'heure de début est obligatoire')],
            ])
            ->add('dureeMinutes', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'constraints' => [new Positive(message: 'La durée doit être positive')],
            ])
            ->add('typeActivite', TextType::class, [
                'label' => 'Type d\'activité (ex: Méditation, Exercice)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActiviteProgramme::class,
        ]);
    }
}
