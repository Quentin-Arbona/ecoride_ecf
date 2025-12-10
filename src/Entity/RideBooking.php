<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Repository\RideBookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RideBookingRepository::class)]
class RideBooking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ride $ride = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $passenger = null;

    #[ORM\Column]
    private ?int $seatsBooked = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    private ?BookingStatus $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $rating = null; // Note de 1 à 5

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $feedbackAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resolutionNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $resolvedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\OneToOne(targetEntity: Review::class, mappedBy: 'booking')]
    private ?Review $review = null;

    # Méthodes pour le calcul du prix et les changements de statut
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = BookingStatus::PENDING;
    }

    public function calculateTotalPrice(): self
    {
        if ($this->ride) {
            $this->totalPrice = $this->ride->getPricePerSeat() * $this->seatsBooked;
        }
        return $this;
    }

    public function confirm(): self
    {
        $this->status = BookingStatus::CONFIRMED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function cancel(): self
    {
        $this->status = BookingStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::CANCELLED;
    }
    

    # Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRide(): ?Ride
    {
        return $this->ride;
    }

    public function setRide(?Ride $ride): static
    {
        $this->ride = $ride;

        return $this;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): static
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getSeatsBooked(): ?int
    {
        return $this->seatsBooked;
    }

    public function setSeatsBooked(int $seatsBooked): static
    {
        $this->seatsBooked = $seatsBooked;

        return $this;
    }

    public function getStatus(): ?BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFeedback(): ?string 
    { 
        return $this->feedback; 
    }
    
    public function setFeedback(?string $feedback): static 
    { 
        $this->feedback = $feedback; 
        
        return $this; 
    }

    public function getRating(): ?int 
    { 
        return $this->rating; 
    }
    
    public function setRating(?int $rating): static 
    { 
        $this->rating = $rating; 
        
        return $this; 
    }

    public function getFeedbackAt(): ?\DateTimeImmutable 
    { 
        return $this->feedbackAt; 
    }

    public function setFeedbackAt(?\DateTimeImmutable $feedbackAt): static 
    { 
        $this->feedbackAt = $feedbackAt; 
        
        return $this; 
    }

    public function getValidatedAt(): ?\DateTimeImmutable 
    { 
        return $this->validatedAt; 
    }
    
    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static 
    { 
        $this->validatedAt = $validatedAt; 
        
        return $this; 
    }

    public function isValidated(): bool
    {
        return $this->validatedAt !== null;
    }

    public function getResolutionNotes(): ?string
    {
        return $this->resolutionNotes;
    }

    public function setResolutionNotes(?string $resolutionNotes): static
    {
        $this->resolutionNotes = $resolutionNotes;
        return $this;
    }

    public function getResolvedBy(): ?User
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?User $resolvedBy): static
    {
        $this->resolvedBy = $resolvedBy;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;
        if ($review !== null && $review->getBooking() !== $this) {
            $review->setBooking($this);
        }
        return $this;
    }

    public function isDisputed(): bool
    {
        return $this->status === BookingStatus::DISPUTED;
    }

    public function hasReview(): bool
    {
        return $this->review !== null;
    }


}
