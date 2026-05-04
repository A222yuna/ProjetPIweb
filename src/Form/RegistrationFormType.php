<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, ['constraints' => [new NotBlank(), new Length(max: 100)]])
            ->add('nom', TextType::class, ['constraints' => [new NotBlank(), new Length(max: 100)]])
            ->add('email', EmailType::class, ['constraints' => [new NotBlank(), new Length(max: 150)]])
            ->add('telephone', TextType::class, ['required' => false, 'constraints' => [new Length(max: 30)]])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Patient' => 'Patient',
                    'Psychologue' => 'Psychologue',
                ],
            ])
            ->add('presentation', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
                'label' => 'Présentation professionnelle (uniquement pour les psychologues)',
                'attr' => ['rows' => 5, 'placeholder' => 'Décrivez votre expérience, vos spécialités et votre approche...']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [new NotBlank(), new Length(min: 8, max: 255)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
