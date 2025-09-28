<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
    public function __construct(
        private TransactionRepository $transactionRepository,
    ) {
    }

    #[Route('/', name: 'app_transactions')]
    public function index(Request $request): Response
    {
        $type = $request->query->get('type');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $criteria = ['player' => $this->getUser()];
        if ($type && in_array($type, [
            Transaction::TYPE_DEPOSIT,
            Transaction::TYPE_BONUS,
            Transaction::TYPE_BET,
            Transaction::TYPE_WIN,
            Transaction::TYPE_WITHDRAWAL,
            Transaction::TYPE_REFUND,
            Transaction::TYPE_ADJUSTMENT,
        ])) {
            $criteria['type'] = $type;
        }

        $transactions = $this->transactionRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        $totalTransactions = $this->transactionRepository->count($criteria);
        $totalPages = ceil($totalTransactions / $limit);

        return $this->render('transactions/index.html.twig', [
            'transactions' => $transactions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'currentType' => $type,
            'totalTransactions' => $totalTransactions,
        ]);
    }

    #[Route('/{id}', name: 'app_transaction_details')]
    public function details(Transaction $transaction): Response
    {
        // Ensure user can only see their own transactions
        if ($transaction->getPlayer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('transactions/details.html.twig', [
            'transaction' => $transaction,
        ]);
    }
}
