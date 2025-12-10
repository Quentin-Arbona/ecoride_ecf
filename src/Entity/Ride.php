<?php

namespace App\Entity;

use App\Entity\User;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RideRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: RideRepository::class)]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $departureDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $departureTime = null;

    #[ORM\Column(length: 255)]
    private ?string $departurePlace = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $arrivalDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $arrivalTime = null;

    #[ORM\Column(length: 255)]
    private ?string $arrivalPlace = null;

    #[ORM\Column(enumType: RideStatus::class)]
    private ?RideStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'ridesAsDriver')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'rides')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Car $car = null;

    #[ORM\Column]
    private ?int $availableSeats = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $pricePerSeat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, RideBooking>
     */
    #[ORM\OneToMany(targetEntity: RideBooking::class, mappedBy: 'ride', orphanRemoval: true)]
    private Collection $bookings;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $estimatedDuration = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $endedAt = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = RideStatus::PENDING;
    }

    public function getConfirmedBookings(): Collection
    {
        return $this->bookings->filter(fn(RideBooking $booking) => 
            $booking->getStatus() === BookingStatus::CONFIRMED
        );
    }

    public function getPendingBookings(): Collection
    {
        return $this->bookings->filter(fn(RideBooking $booking) => 
            $booking->getStatus() === BookingStatus::PENDING
        );
    }

    public function getBookedSeatsCount(): int
    {
        $total = 0;
        foreach ($this->getConfirmedBookings() as $booking) {
            $total += $booking->getSeatsBooked();
        }
        return $total;
    }

    public function getRemainingSeats(): int
    {
        return $this->availableSeats - $this->getBookedSeatsCount();
    }

    public function hasAvailableSeats(int $requestedSeats = 1): bool
    {
        return $this->getRemainingSeats() >= $requestedSeats;
    }

    public function hasPassenger(User $user): bool
    {
        foreach ($this->bookings as $booking) {
            if ($booking->getPassenger() === $user && 
                $booking->getStatus() !== BookingStatus::CANCELLED) {
                return true;
            }
        }
        return false;
    }

    public function getPassengerBooking(User $user): ?RideBooking
    {
        foreach ($this->bookings as $booking) {
            if ($booking->getPassenger() === $user) {
                return $booking;
            }
        }
        return null;
    }

    public function getDepartureDateTime(): \DateTimeInterface
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->departureDate->format('Y-m-d') . ' ' . $this->departureTime->format('H:i:s'));
    }

    public function getArrivalDateTime(): \DateTimeInterface
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->arrivalDate->format('Y-m-d') . ' ' . $this->arrivalTime->format('H:i:s'));
    }

    public function getDuration(): \DateInterval
    {
        return $this->getDepartureDateTime()->diff($this->getArrivalDateTime());
    }

    public function start(): void
{
    if ($this->status !== RideStatus::PENDING) {
        throw new \LogicException('Le trajet ne peut être démarré que s\'il est en attente.');
    }

    $this->status = RideStatus::ACTIVE;
    $this->startedAt = new \DateTime();
    $this->updatedAt = new \DateTimeImmutable();
}

public function end(): void
{
    if ($this->status !== RideStatus::ACTIVE) {
        throw new \LogicException('Le trajet ne peut être terminé que s\'il est en cours.');
    }

    $this->status = RideStatus::COMPLETED;
    $this->endedAt = new \DateTime();
    $this->updatedAt = new \DateTimeImmutable();
}

public function isInProgress(): bool
{
    return $this->status === RideStatus::ACTIVE;
}

public function isCompleted(): bool
{
    return $this->status === RideStatus::COMPLETED;
}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartureDate(): ?\DateTimeImmutable
    {
        return $this->departureDate;
    }

    public function setDepartureDate(\DateTimeImmutable $departureDate): static
    {
        $this->departureDate = $departureDate;
        return $this;
    }

    public function getDepartureTime(): ?\DateTimeImmutable
    {
        return $this->departureTime;
    }

    public function setDepartureTime(\DateTimeImmutable $departureTime): static
    {
        $this->departureTime = $departureTime;
        return $this;
    }

    public function getDeparturePlace(): ?string
    {
        return $this->departurePlace;
    }

    public function setDeparturePlace(string $departurePlace): static
    {
        $this->departurePlace = $departurePlace;
        return $this;
    }

    public function getArrivalDate(): ?\DateTimeImmutable
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(?\DateTimeImmutable $arrivalDate): static
    {
        $this->arrivalDate = $arrivalDate;
        return $this;
    }

    public function getArrivalTime(): ?\DateTimeImmutable
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTimeImmutable $arrivalTime): static
    {
        $this->arrivalTime = $arrivalTime;
        return $this;
    }

    public function getArrivalPlace(): ?string
    {
        return $this->arrivalPlace;
    }

    public function setArrivalPlace(string $arrivalPlace): static
    {
        $this->arrivalPlace = $arrivalPlace;
        return $this;
    }

    public function getStatus(): ?RideStatus
    {
        return $this->status;
    }

    public function setStatus(RideStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;
        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;
        return $this;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function setAvailableSeats(int $availableSeats): static
    {
        $this->availableSeats = $availableSeats;
        return $this;
    }

    public function getPricePerSeat(): ?string
    {
        return $this->pricePerSeat;
    }

    public function setPricePerSeat(string $pricePerSeat): static
    {
        $this->pricePerSeat = $pricePerSeat;
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

    /**
     * @return Collection<int, RideBooking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(RideBooking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setRide($this);
        }
        return $this;
    }

    public function removeBooking(RideBooking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getRide() === $this) {
                $booking->setRide(null);
            }
        }
        return $this;
    }

    public function getEstimatedDuration(): ?int
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(int $estimatedDuration): self
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt(): ?\DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTime $endedAt): static
    {
        $this->endedAt = $endedAt;
        return $this;
    }
}