<?php

namespace App\Entity;

use App\Repository\GameRoundRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRoundRepository::class)]
class GameRound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gameRounds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameSession $gameSession = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column]
    private ?float $betAmount = null;

    #[ORM\Column]
    private ?float $winAmount = null;

    #[ORM\Column]
    private ?float $netResult = null;

    #[ORM\Column]
    private ?float $balanceBefore = null;

    #[ORM\Column]
    private ?float $balanceAfter = null;

    #[ORM\Column]
    private array $reelsResult = [];

    #[ORM\Column]
    private array $paylinesWon = [];

    #[ORM\Column]
    private array $multipliers = [];

    #[ORM\Column]
    private array $bonusFeatures = [];

    #[ORM\Column]
    private ?int $linesPlayed = null;

    #[ORM\Column]
    private ?float $betPerLine = null;

    #[ORM\Column]
    private ?float $rtpContribution = null;

    #[ORM\Column]
    private ?bool $isBonusRound = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $bonusType = null;

    #[ORM\Column(nullable: true)]
    private ?int $freeSpinsRemaining = null;

    #[ORM\Column(length: 255)]
    private ?string $transectionRef = null;

    #[ORM\Column(length: 64)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 64)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $complitedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $completionHash = null;

    #[ORM\Column]
    private array $extraData = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): static
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

    public function getBetAmount(): ?float
    {
        return $this->betAmount;
    }

    public function setBetAmount(float $betAmount): static
    {
        $this->betAmount = $betAmount;

        return $this;
    }

    public function getWinAmount(): ?float
    {
        return $this->winAmount;
    }

    public function setWinAmount(float $winAmount): static
    {
        $this->winAmount = $winAmount;

        return $this;
    }

    public function getNetResult(): ?float
    {
        return $this->netResult;
    }

    public function setNetResult(float $netResult): static
    {
        $this->netResult = $netResult;

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

    public function getReelsResult(): array
    {
        return $this->reelsResult;
    }

    public function setReelsResult(array $reelsResult): static
    {
        $this->reelsResult = $reelsResult;

        return $this;
    }

    public function getPaylinesWon(): array
    {
        return $this->paylinesWon;
    }

    public function setPaylinesWon(array $paylinesWon): static
    {
        $this->paylinesWon = $paylinesWon;

        return $this;
    }

    public function getMultipliers(): array
    {
        return $this->multipliers;
    }

    public function setMultipliers(array $multipliers): static
    {
        $this->multipliers = $multipliers;

        return $this;
    }

    public function getBonusFeatures(): array
    {
        return $this->bonusFeatures;
    }

    public function setBonusFeatures(array $bonusFeatures): static
    {
        $this->bonusFeatures = $bonusFeatures;

        return $this;
    }

    public function getLinesPlayed(): ?int
    {
        return $this->linesPlayed;
    }

    public function setLinesPlayed(int $linesPlayed): static
    {
        $this->linesPlayed = $linesPlayed;

        return $this;
    }

    public function getBetPerLine(): ?float
    {
        return $this->betPerLine;
    }

    public function setBetPerLine(float $betPerLine): static
    {
        $this->betPerLine = $betPerLine;

        return $this;
    }

    public function getRtpContribution(): ?float
    {
        return $this->rtpContribution;
    }

    public function setRtpContribution(float $rtpContribution): static
    {
        $this->rtpContribution = $rtpContribution;

        return $this;
    }

    public function isBonusRound(): ?bool
    {
        return $this->isBonusRound;
    }

    public function setIsBonusRound(bool $isBonusRound): static
    {
        $this->isBonusRound = $isBonusRound;

        return $this;
    }

    public function getBonusType(): ?string
    {
        return $this->bonusType;
    }

    public function setBonusType(?string $bonusType): static
    {
        $this->bonusType = $bonusType;

        return $this;
    }

    public function getFreeSpinsRemaining(): ?int
    {
        return $this->freeSpinsRemaining;
    }

    public function setFreeSpinsRemaining(?int $freeSpinsRemaining): static
    {
        $this->freeSpinsRemaining = $freeSpinsRemaining;

        return $this;
    }

    public function getTransectionRef(): ?string
    {
        return $this->transectionRef;
    }

    public function setTransectionRef(string $transectionRef): static
    {
        $this->transectionRef = $transectionRef;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

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

    public function getComplitedAt(): ?\DateTimeImmutable
    {
        return $this->complitedAt;
    }

    public function setComplitedAt(\DateTimeImmutable $complitedAt): static
    {
        $this->complitedAt = $complitedAt;

        return $this;
    }

    public function getCompletionHash(): ?string
    {
        return $this->completionHash;
    }

    public function setCompletionHash(string $completionHash): static
    {
        $this->completionHash = $completionHash;

        return $this;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function setExtraData(array $extraData): static
    {
        $this->extraData = $extraData;

        return $this;
    }
}
