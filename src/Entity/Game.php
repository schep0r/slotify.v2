<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 64)]
    private ?string $slug = null;

    #[ORM\Column(length: 64)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $minBet = null;

    #[ORM\Column]
    private ?float $maxBet = null;

    #[ORM\Column]
    private ?float $stepBet = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(nullable: true)]
    private ?float $rtp = null;

    #[ORM\Column(nullable: true)]
    private ?array $reels = null;

    #[ORM\Column(nullable: true)]
    private ?array $paylines = null;

    #[ORM\Column]
    private array $paytable = [];

    #[ORM\Column]
    private ?int $rows = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getMinBet(): ?float
    {
        return $this->minBet;
    }

    public function setMinBet(float $minBet): static
    {
        $this->minBet = $minBet;

        return $this;
    }

    public function getMaxBet(): ?float
    {
        return $this->maxBet;
    }

    public function setMaxBet(float $maxBet): static
    {
        $this->maxBet = $maxBet;

        return $this;
    }

    public function getStepBet(): ?float
    {
        return $this->stepBet;
    }

    public function setStepBet(float $stepBet): static
    {
        $this->stepBet = $stepBet;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getRtp(): ?float
    {
        return $this->rtp;
    }

    public function setRtp(?float $rtp): static
    {
        $this->rtp = $rtp;

        return $this;
    }

    public function getReels(): ?array
    {
        return $this->reels;
    }

    public function setReels(?array $reels): static
    {
        $this->reels = $reels;

        return $this;
    }

    public function getPaylines(): ?array
    {
        return $this->paylines;
    }

    public function setPaylines(?array $paylines): static
    {
        $this->paylines = $paylines;

        return $this;
    }

    public function getPaytable(): array
    {
        return $this->paytable;
    }

    public function setPaytable(array $paytable): static
    {
        $this->paytable = $paytable;

        return $this;
    }

    public function getRows(): ?int
    {
        return $this->rows;
    }

    public function setRows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }
}
