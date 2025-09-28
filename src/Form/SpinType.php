<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $gameObject = $options['game_object'];
        $isActiveFreeSpins = $options['active_free_spins'];
        $gamePaylines = [];
        foreach ($gameObject->getPaylines() as $index => $payline) { // Assuming a method to get a collection
            $gamePaylines[implode('-', $payline)] = $index; // Customize label and value as needed
        }

        $builder
            ->setMethod('POST')
            ->add('betAmount', NumberType::class, [
                'label' => 'Bet Amount',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => $gameObject->getMinBet(),
                    'max' => $gameObject->getMaxBet(),
                    'step' => $gameObject->getStepBet(),
                    'class' => 'bet-input',
                ],
            ])
            ->add(
                'activePaylines',
                ChoiceType::class,
                [
                    'label' => 'Select Paylines',
                    'choices' => $gamePaylines,
                    'multiple' => true,
                    'data' => [0],
                ]
            )
            ->add(
                'useFreeSpins',
                HiddenType::class,
                [
                    'data' => $isActiveFreeSpins,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            // Configure your form options here
        ]);

        $resolver->setRequired(['game_object']);
        $resolver->setAllowedTypes('game_object', Game::class);

        $resolver->setRequired(['active_free_spins']);
        $resolver->setAllowedTypes('active_free_spins', 'boolean');
    }
}
