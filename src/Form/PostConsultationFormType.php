<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PostConsultationFormType extends AbstractType
{
    private const CATEGORY_CHOICES = [
        'Vie Émotionnelle et Bien-être' => 'Vie Émotionnelle et Bien-être',
        'Relations et Liens Sociaux' => 'Relations et Liens Sociaux',
        'Troubles et Santé Mentale' => 'Troubles et Santé Mentale',
        'Monde du Travail et Études' => 'Monde du Travail et Études',
        'Espace Débats et Société' => 'Espace Débats et Société',
    ];

    public static function getCategoryChoices(): array
    {
        return self::CATEGORY_CHOICES;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $options['category_choices'] ?: self::CATEGORY_CHOICES;

        if (array_values($choices) === $choices) {
            $choices = array_combine($choices, $choices);
        }

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
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $choices,
                'placeholder' => 'Choisir une catégorie',
                'constraints' => [
                    new NotBlank(message: 'La catégorie est obligatoire.'),
                ],
                'help' => 'Sélectionnez une catégorie disponible.',
            ])
            ->add('imageUrl', TextType::class, [
                'label' => 'Image (Lien externe)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                ],
                'help' => 'Collez ici l\'adresse directe d\'une image.',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Ou : Télécharger une image (Fichier)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG ou WEBP).',
                    ])
                ],
                'help' => 'Taille max 2Mo. L\'upload est prioritaire sur le lien externe.',
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
