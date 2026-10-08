<?php

namespace App\Form;

use App\Entity\Brand;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BrandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la marque',
                'constraints' => [new Assert\NotBlank(message: 'La marque est obligatoire.')],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'empty_data' => '',
                'help' => 'Généré automatiquement si vide.',
            ])
            ->add('country', TextType::class, ['label' => 'Pays d’origine', 'required' => false])
            ->add('logo', TextType::class, ['label' => 'Logo (URL)', 'required' => false])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('isFeatured', CheckboxType::class, ['label' => 'Afficher comme partenaire', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Brand::class]);
    }
}