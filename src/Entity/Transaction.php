<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    /**
     * Transaction types.
     */
    public const TYPE_BET = 'bet';
    public const TYPE_WIN = 'win';
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Transaction statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: true)]
    private ?GameSession $gameSession = null;

    #[ORM\Column(length: 64)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?float $balanceBefore = null;

    #[ORM\Column]
    private ?float $balanceAfter = null;

    #[ORM\Column(nullable: true)]
    private ?array $spinResult = null;

    #[ORM\Column(length: 255)]
    private ?string $referenceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(length: 64)]
    private ?string $status = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?UserInterface $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getGameSession(): ?GameSession
    {
        return $this->gameSession;
    }

    public function setGameSession(?GameSession $gameSession): static
    {
        $this->gameSession = $gameSession;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBalanceBefore(): ?float
    {
        return $this->balanceBefore;
    }

    public function setBalanceBefore(float $balanceBefore): static
    {
        $this->balanceBefore = $balanceBefore;

        return $this;
    }

    public function getBalanceAfter(): ?float
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(float $balanceAfter): static
    {
        $this->balanceAfter = $balanceAfter;

        return $this;
    }

    public function getSpinResult(): ?array
    {
        return $this->spinResult;
    }

    public function setSpinResult(array $spinResult): static
    {
        $this->spinResult = $spinResult;

        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): static
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
