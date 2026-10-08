<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\DataTransformer\CommaListTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [new Assert\NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'required' => false,
                'empty_data' => '',
                'help' => 'Laissez vide pour générer automatiquement.',
            ])
            ->add('sku', TextType::class, ['label' => 'Référence (SKU)'])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Sans catégorie',
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix de vente (DA)',
                'divisor' => 100,
                'constraints' => [new Assert\PositiveOrZero()],
            ])
            ->add('salePrice', MoneyType::class, [
                'label' => 'Prix barré (DA)',
                'divisor' => 100,
                'required' => false,
                'help' => 'Remplissez pour afficher une promotion.',
            ])
            ->add('stock', IntegerType::class, ['label' => 'Stock'])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Description courte',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => ['rows' => 6],
            ])
            ->add('mainImage', TextType::class, [
                'label' => 'Image principale (URL)',
                'required' => false,
                'help' => 'Ex: /images/produit-1.jpg',
            ])
            ->add('sizes', TextType::class, [
                'label' => 'Tailles',
                'required' => false,
                'help' => 'Séparées par des virgules. Ex: S, M, L, XL',
            ])
            ->add('colors', TextType::class, [
                'label' => 'Couleurs',
                'required' => false,
                'help' => 'Séparées par des virgules. Ex: Noir, Beige',
            ])
            ->add('materials', TextType::class, [
                'label' => 'Matières',
                'required' => false,
                'help' => 'Séparées par des virgules. Ex: Coton, Lin',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Femme' => 'femme',
                    'Homme' => 'homme',
                    'Unisexe' => 'unisexe',
                    'Enfant' => 'enfant',
                ],
            ])
            ->add('isFeatured', CheckboxType::class, ['label' => 'Mis en avant', 'required' => false])
            ->add('isNew', CheckboxType::class, ['label' => 'Nouveauté', 'required' => false])
            ->add('isActive', CheckboxType::class, ['label' => 'Visible sur le site', 'required' => false]);

        $transformer = new CommaListTransformer();
        $builder->get('sizes')->addModelTransformer($transformer);
        $builder->get('colors')->addModelTransformer(clone $transformer);
        $builder->get('materials')->addModelTransformer(clone $transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}