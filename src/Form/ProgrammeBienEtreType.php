<?php

namespace App\Form;

use App\Entity\ProgrammeBienEtre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ProgrammeBienEtreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire')],
            ])
            ->add('objectif', TextareaType::class, [
                'required' => false,
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (jours)',
                'constraints' => [
                    new NotBlank(message: 'La durée est obligatoire'),
                    new Positive(message: 'La durée doit être positive'),
                ],
            ])
            ->add('niveauDifficulte', ChoiceType::class, [
                'choices' => [
                    'Facile' => 'Facile',
                    'Intermédiaire' => 'Intermédiaire',
                    'Difficile' => 'Difficile',
                ],
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'Brouillon',
                    'Publié' => 'Publié',
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgrammeBienEtre::class,
        ]);
    }
}
