<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ForumReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $reasons */
        $reasons = $options['reasons'];

        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Motif',
                'choices' => $reasons,
                'placeholder' => 'Choisir un motif…',
                'constraints' => [
                    new NotBlank(message: 'Le motif est obligatoire.'),
                ],
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Détails (optionnel)',
                'required' => false,
                'constraints' => [
                    new Length(max: 2000),
                ],
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ajoutez des détails utiles (ex: insulte, spam, contenu inapproprié)…',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'forum_report',
            'reasons' => [
                'Spam' => 'spam',
                'Harcèlement / insultes' => 'abuse',
                'Contenu inapproprié' => 'inappropriate',
                'Autre' => 'other',
            ],
        ]);

        $resolver->setAllowedTypes('reasons', 'array');
    }
}

