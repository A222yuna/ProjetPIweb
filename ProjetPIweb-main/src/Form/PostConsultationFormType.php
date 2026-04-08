<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PostConsultationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire.'),
                    new Length(max: 255),
                ],
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Sujet de votre publication',
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new NotBlank(message: 'Le contenu est obligatoire.'),
                ],
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez votre question ou partagez votre expérience…',
                ],
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'constraints' => [
                    new NotBlank(message: 'La catégorie est obligatoire.'),
                    new Length(max: 100),
                ],
                'attr' => [
                    'maxlength' => 100,
                    'class' => 'input-categorie',
                    'list' => 'consultation-categories',
                    'placeholder' => 'Ex. Discussion Générale, Anxiété, Thérapie de couple…',
                ],
                'help' => 'Colonne categorie (varchar 100). Les valeurs déjà en base sont proposées dans la liste.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'category_choices' => [],
        ]);
        $resolver->setAllowedTypes('category_choices', 'array');
    }
}
