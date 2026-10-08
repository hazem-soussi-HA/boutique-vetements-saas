<?php

namespace App\Form;

use App\Entity\ShopSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShopSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shopName', TextType::class, ['label' => 'Nom de la boutique'])
            ->add('tagline', TextType::class, ['label' => 'Slogan', 'required' => false])
            ->add('heroTitle', TextType::class, ['label' => 'Titre du hero', 'required' => false])
            ->add('heroSubtitle', TextareaType::class, [
                'label' => 'Sous-titre du hero',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('heroImage', TextType::class, ['label' => 'Image du hero (URL)', 'required' => false])
            ->add('announcement', TextType::class, [
                'label' => 'Bandeau d’annonce',
                'required' => false,
                'help' => 'Laissez vide pour masquer le bandeau.',
            ])
            ->add('contactEmail', TextType::class, ['label' => 'E-mail de contact'])
            ->add('contactPhone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('contactAddress', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('facebookUrl', TextType::class, ['label' => 'Facebook', 'required' => false])
            ->add('instagramUrl', TextType::class, ['label' => 'Instagram', 'required' => false])
            ->add('tiktokUrl', TextType::class, ['label' => 'TikTok', 'required' => false])
            ->add('whatsappUrl', TextType::class, ['label' => 'WhatsApp', 'required' => false])
            ->add('freeShippingThreshold', MoneyType::class, [
                'label' => 'Livraison offerte à partir de',
                'divisor' => 100,
                'required' => false,
                'help' => '0 pour désactiver la livraison offerte.',
            ])
            ->add('shippingFee', MoneyType::class, [
                'label' => 'Frais de livraison',
                'divisor' => 100,
            ])
            ->add('vatRate', IntegerType::class, ['label' => 'Taux de TVA (%)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ShopSettings::class]);
    }
}