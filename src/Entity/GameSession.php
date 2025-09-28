<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gameSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(length: 255)]
    private ?string $sessionToken = null;

    #[ORM\Column]
    private ?int $totalSpins = null;

    #[ORM\Column]
    private ?float $totalBet = null;

    #[ORM\Column]
    private ?float $totalWin = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(length: 64)]
    private ?string $status = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'gameSession')]
    private Collection $transactions;

    /**
     * @var Collection<int, GameRound>
     */
    #[ORM\OneToMany(targetEntity: GameRound::class, mappedBy: 'gameSession')]
    private Collection $gameRounds;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->gameRounds = new ArrayCollection();
    }

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

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    public function getTotalSpins(): ?int
    {
        return $this->totalSpins;
    }

    public function setTotalSpins(int $totalSpins): static
    {
        $this->totalSpins = $totalSpins;

        return $this;
    }

    public function getTotalBet(): ?float
    {
        return $this->totalBet;
    }

    public function setTotalBet(float $totalBet): static
    {
        $this->totalBet = $totalBet;

        return $this;
    }

    public function getTotalWin(): ?float
    {
        return $this->totalWin;
    }

    public function setTotalWin(float $totalWin): static
    {
        $this->totalWin = $totalWin;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

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

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setGameSession($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getGameSession() === $this) {
                $transaction->setGameSession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GameRound>
     */
    public function getGameRounds(): Collection
    {
        return $this->gameRounds;
    }

    public function addGameRound(GameRound $gameRound): static
    {
        if (!$this->gameRounds->contains($gameRound)) {
            $this->gameRounds->add($gameRound);
            $gameRound->setGameSession($this);
        }

        return $this;
    }

    public function removeGameRound(GameRound $gameRound): static
    {
        if ($this->gameRounds->removeElement($gameRound)) {
            // set the owning side to null (unless already changed)
            if ($gameRound->getGameSession() === $this) {
                $gameRound->setGameSession(null);
            }
        }

        return $this;
    }
}
