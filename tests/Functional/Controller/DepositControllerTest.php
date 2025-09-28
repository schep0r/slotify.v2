<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enums\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DepositControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDepositPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/deposit/');
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertRouteSame('app_login');
    }

    public function testDepositPageDisplaysForm(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/deposit/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Make a Deposit');
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="deposit[amount]"]');
        $this->assertSelectorExists('input[name="deposit[paymentMethod]"]');
    }

    public function testSuccessfulDepositSubmission(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/deposit/');
        
        $form = $crawler->selectButton('Make Deposit')->form([
            'deposit[amount]' => '50.00',
            'deposit[paymentMethod]' => PaymentMethod::DUMMY->value,
        ]);

        $this->client->submit($form);

        // Should redirect to success page
        $this->assertResponseRedirects();
        $response = $this->client->followRedirect();
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Deposit Successful');
        
        // Check that deposit transaction was created in database
        $transaction = $this->entityManager->getRepository(Transaction::class)
            ->findOneBy([
                'player' => $user,
                'type' => Transaction::TYPE_DEPOSIT
            ]);
        
        $this->assertNotNull($transaction);
        $this->assertEquals(50.0, $transaction->getAmount());
        $this->assertEquals(PaymentMethod::DUMMY->value, $transaction->getPaymentMethod());
        $this->assertEquals(Transaction::STATUS_COMPLETED, $transaction->getStatus());
        
        // Check that user balance was updated
        $this->entityManager->refresh($user);
        $this->assertEquals(150.0, $user->getBalance()); // 100 + 50
    }

    public function testInvalidDepositAmount(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/deposit/');
        
        $form = $crawler->selectButton('Make Deposit')->form([
            'deposit[amount]' => '0.00', // Invalid amount
            'deposit[paymentMethod]' => PaymentMethod::DUMMY->value,
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful(); // Form validation error, stays on same page
        $this->assertSelectorExists('.form-error, .invalid-feedback'); // Check for validation error
    }

    public function testDepositHistoryPage(): void
    {
        $user = $this->createTestUser();
        $this->createTestDepositTransaction($user, 25.0, PaymentMethod::DUMMY->value, Transaction::STATUS_COMPLETED);
        $this->createTestDepositTransaction($user, 75.0, PaymentMethod::CARD->value, Transaction::STATUS_PENDING);
        
        $this->client->loginUser($user);

        $this->client->request('GET', '/deposit/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Deposit History');
        
        // Check that both deposits are displayed
        $this->assertSelectorTextContains('tbody', '$25.00');
        $this->assertSelectorTextContains('tbody', '$75.00');
        $this->assertSelectorTextContains('tbody', 'Completed');
        $this->assertSelectorTextContains('tbody', 'Pending');
    }

    public function testDepositSuccessPageAccessControl(): void
    {
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');
        
        $transaction = $this->createTestDepositTransaction($user1, 50.0, PaymentMethod::DUMMY->value, Transaction::STATUS_COMPLETED);
        
        // User2 tries to access User1's deposit success page
        $this->client->loginUser($user2);
        
        $this->client->request('GET', '/deposit/success/' . $transaction->getId());
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEmptyDepositHistory(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/deposit/history');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.text-center', 'No deposits yet');
        $this->assertSelectorExists('a[href*="/deposit/"]'); // Link to make first deposit
    }

    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed_password');
        $user->setBalance(100.0);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestDepositTransaction(User $user, float $amount, string $paymentMethod, string $status): Transaction
    {
        $transaction = new Transaction();
        $transaction->setPlayer($user);
        $transaction->setType(Transaction::TYPE_DEPOSIT);
        $transaction->setAmount($amount);
        $transaction->setBalanceBefore($user->getBalance());
        $transaction->setBalanceAfter($user->getBalance() + $amount);
        $transaction->setPaymentMethod($paymentMethod);
        $transaction->setStatus($status);
        $transaction->setReferenceId(uniqid('test_dep_', true));
        $transaction->setDescription('Test deposit');
        $transaction->setCreatedAt(new \DateTimeImmutable());
        $transaction->setGameSession(null);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test data
        $this->entityManager->createQuery('DELETE FROM App\Entity\Transaction')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }
}