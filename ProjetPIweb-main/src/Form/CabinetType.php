<?php

namespace App\Form;

use App\Entity\Cabinet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CabinetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresse', null, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('ville', null, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('horaires', null, [
                'required' => false,
                'label' => 'Horaires',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cabinet::class,
        ]);
    }
}
