<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AppLoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppLoginAuthenticator $authenticator,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set initial balance for new users
            $welcomeBonusAmount = 100.00; // $100 welcome bonus
            $user->setBalance($welcomeBonusAmount);

            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Create welcome bonus transaction record
            $welcomeTransaction = new Transaction();
            $welcomeTransaction->setPlayer($user);
            $welcomeTransaction->setType(Transaction::TYPE_BONUS);
            $welcomeTransaction->setAmount($welcomeBonusAmount);
            $welcomeTransaction->setBalanceBefore(0.00);
            $welcomeTransaction->setBalanceAfter($welcomeBonusAmount);
            $welcomeTransaction->setReferenceId(uniqid('welcome_', true));
            $welcomeTransaction->setDescription('Welcome bonus for new player');
            $welcomeTransaction->setStatus(Transaction::STATUS_COMPLETED);
            $welcomeTransaction->setPaymentMethod(null);
            $welcomeTransaction->setCreatedAt(new \DateTimeImmutable());
            $welcomeTransaction->setGameSession(null);
            $welcomeTransaction->setMetadata([
                'bonus_type' => 'welcome',
                'reason' => 'New player registration',
            ]);

            $entityManager->persist($welcomeTransaction);
            $entityManager->flush();

            // Automatically log in the user after registration
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
