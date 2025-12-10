<?php 

namespace App\Entity;


use App\Enum\ReviewStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReviewRepository;

#[ORM\Entity]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author; // Utilisateur qui laisse l'avis (passager)

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver; // Chauffeur concerné par l'avis

    #[ORM\ManyToOne(targetEntity: Ride::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ride $ride; // Trajet concerné par l'avis

    #[ORM\OneToOne(targetEntity: RideBooking::class, inversedBy: 'review')]
    #[ORM\JoinColumn(nullable: true)]
    private ?RideBooking $booking = null; // Réservation associée à l'avis

    #[ORM\Column(type: Types::TEXT)]
    private string $content; // Contenu de l'avis

    #[ORM\Column(type: Types::SMALLINT)]
    private int $rating; // Note (ex: 1 à 5)

    #[ORM\Column(enumType: ReviewStatus::class)]
    private ReviewStatus $status; // Statut : en_attente, valide, refuse

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $validatedBy; // Employé qui valide/refuse l'avis (nullable)

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = ReviewStatus::PENDING;
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(User $driver): static
    {
        $this->driver = $driver;
        return $this;
    }

    public function getRide(): ?Ride
    {
        return $this->ride;
    }

    public function setRide(Ride $ride): static
    {
        $this->ride = $ride;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getBooking(): ?RideBooking
    {
        return $this->booking;
    }

    public function setBooking(?RideBooking $booking): static
    {
        $this->booking = $booking;
        return $this;
    }
}