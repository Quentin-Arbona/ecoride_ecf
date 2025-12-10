<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]  
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette adresse email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_EMPLOYE = 'ROLE_EMPLOYE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private ?string $password = null;

     #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $createAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $updateAt;

    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column]
    private bool $isVerified = false;

    // Callbacks pour automatiser createAt et updateAt
    #[ORM\PrePersist]
    public function setCreateAtValue(): void
    {
        $this->createAt = new \DateTimeImmutable();
        $this->updateAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdateAtValue(): void
    {
        $this->updateAt = new \DateTimeImmutable();
    }

    /**
     * @var Collection<int, Car>
     */
    #[ORM\OneToMany(targetEntity: Car::class, mappedBy: 'owner')]
    private Collection $cars;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'driver')]
    private Collection $ridesAsDriver;

    /**
     * @var Collection<int, RideBooking>
     */
    #[ORM\OneToMany(targetEntity: RideBooking::class, mappedBy: 'passenger')]
    private Collection $bookings;

    # Tableau des voitures, tableau des trajets d'un conducteur, et tableau des réservations.
    public function __construct()
    {
        $this->cars = new ArrayCollection();
        $this->ridesAsDriver = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    #[ORM\Column(type: 'integer')]
    private int $credits = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    public function hasCars(): bool
    {
        return !$this->cars->isEmpty();
    }

    public function canDrive(): bool
    {
        return $this->hasCars() && $this->isVerified();
    }

    public function hasBookedRide(Ride $ride): bool
    {
        foreach ($this->bookings as $booking) {
            if ($booking->getRide() === $ride && 
                $booking->getStatus() !== \App\Enum\BookingStatus::CANCELLED) {
                return true;
            }
        }
        return false;
    }

    public function getConfirmedBookings(): Collection
    {
        return $this->bookings->filter(fn(RideBooking $booking) => 
            $booking->getStatus() === \App\Enum\BookingStatus::CONFIRMED
        );
    }

    public function getPendingBookings(): Collection
    {
        return $this->bookings->filter(fn(RideBooking $booking) => 
            $booking->getStatus() === \App\Enum\BookingStatus::PENDING
        );
    }

    # Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;
        return $this;
    }

    public function setUpdateAt(?\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;
        return $this;
    }

    

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->setOwner($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            // set the owning side to null (unless already changed)
            if ($car->getOwner() === $this) {
                $car->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesAsDriver(): Collection
    {
        return $this->ridesAsDriver;
    }

    public function addRidesAsDriver(Ride $ridesAsDriver): static
    {
        if (!$this->ridesAsDriver->contains($ridesAsDriver)) {
            $this->ridesAsDriver->add($ridesAsDriver);
            $ridesAsDriver->setDriver($this);
        }

        return $this;
    }

    public function removeRidesAsDriver(Ride $ridesAsDriver): static
    {
        if ($this->ridesAsDriver->removeElement($ridesAsDriver)) {
            // set the owning side to null (unless already changed)
            if ($ridesAsDriver->getDriver() === $this) {
                $ridesAsDriver->setDriver(null);
            }
        }

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
            $booking->setPassenger($this);
        }

        return $this;
    }

    public function removeBooking(RideBooking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getPassenger() === $this) {
                $booking->setPassenger(null);
            }
        }

        return $this;
    }

    public function getBookingForRide(Ride $ride): ?RideBooking
    {
        foreach ($this->bookings as $booking) {
            if ($booking->getRide() === $ride) {
                return $booking;
            }
        }
        return null;
    }

public function getFullName(): string
{
    return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
}

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;
        return $this;
    }

    public function addCredits(int $amount): static
    {
        $this->credits += $amount;
        return $this;
    }

    public function subtractCredits(int $amount): static
    {
        $this->credits = max(0, $this->credits - $amount);
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }
}
