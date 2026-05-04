<?php

namespace App\Form;

use App\Entity\Cabinet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CabinetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresse', TextType::class, [
                'constraints' => [new NotBlank(message: 'L\'adresse est obligatoire')],
            ])
            ->add('ville', TextType::class, [
                'constraints' => [new NotBlank(message: 'La ville est obligatoire')],
            ])
            ->add('horaires', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Lun-Ven 09:00-18:00'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cabinet::class,
        ]);
    }
}
