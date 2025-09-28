<?php

namespace App\Form;

use App\Enums\PaymentMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DepositType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Deposit Amount',
                'currency' => 'USD',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 10000]),
                ],
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => '0.00',
                    'min' => '1',
                    'max' => '10000',
                    'step' => '0.01',
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    PaymentMethod::CARD->getLabel() => PaymentMethod::CARD->value,
                    PaymentMethod::PAYPAL->getLabel() => PaymentMethod::PAYPAL->value,
                    PaymentMethod::DUMMY->getLabel() => PaymentMethod::DUMMY->value,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'class' => 'payment-method-radio',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Make Deposit',
                'attr' => [
                    'class' => 'btn-primary w-full',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Remove data_class since we're using a simple array form
        ]);
    }
}