<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Form\DepositType;
use App\Services\DepositService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/deposit')]
#[IsGranted('ROLE_USER')]
class DepositController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepositService $depositService,
    ) {
    }

    #[Route('/', name: 'app_deposit')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(DepositType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Process the deposit
            $transaction = $this->depositService->processDeposit(
                $this->getUser(),
                $data['amount'],
                $data['paymentMethod']
            );

            if ($transaction) {
                $this->addFlash('success', sprintf(
                    'Deposit of $%.2f has been processed successfully!',
                    $transaction->getAmount()
                ));

                return $this->redirectToRoute('app_deposit_success', [
                    'id' => $transaction->getId(),
                ]);
            } else {
                $this->addFlash('error', 'Deposit processing failed. Please try again.');
            }
        }

        return $this->render('deposit/index.html.twig', [
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/success/{id}', name: 'app_deposit_success')]
    public function success(Transaction $transaction): Response
    {
        // Ensure this is a deposit transaction and user can only see their own
        if (Transaction::TYPE_DEPOSIT !== $transaction->getType()
            || $transaction->getPlayer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('deposit/success.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    #[Route('/history', name: 'app_deposit_history')]
    public function history(): Response
    {
        $deposits = $this->entityManager->getRepository(Transaction::class)
            ->findBy([
                'player' => $this->getUser(),
                'type' => Transaction::TYPE_DEPOSIT,
            ], ['createdAt' => 'DESC']);

        return $this->render('deposit/history.html.twig', [
            'deposits' => $deposits,
        ]);
    }
}
