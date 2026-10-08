<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerFirstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Amina'],
                'constraints' => [new Assert\NotBlank(message: 'Le prénom est obligatoire.')],
            ])
            ->add('customerLastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Belkacem'],
                'constraints' => [new Assert\NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'E-mail',
                'attr' => ['placeholder' => 'amina@exemple.com'],
                'constraints' => [
                    new Assert\NotBlank(message: "L'e-mail est obligatoire."),
                    new Assert\Email(message: "L'e-mail n'est pas valide."),
                ],
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '+213 555 12 34 56'],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'Ville',
                'attr' => ['placeholder' => 'Alger'],
                'constraints' => [new Assert\NotBlank(message: 'La ville est obligatoire.')],
            ])
            ->add('shippingWilaya', TextType::class, [
                'label' => 'Wilaya',
                'required' => false,
                'attr' => ['placeholder' => 'Alger'],
            ])
            ->add('shippingAddress', TextType::class, [
                'label' => 'Adresse complète',
                'attr' => ['placeholder' => 'Cité 200 logements, Bât. B'],
                'constraints' => [new Assert\NotBlank(message: "L'adresse est obligatoire.")],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement à la livraison' => Order::PAYMENT_COD,
                    'Virement bancaire' => Order::PAYMENT_BANK,
                ],
                'expanded' => true,
                'multiple' => false,
                'placeholder' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Note pour la commande',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Précisions de livraison, heure préférée...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}