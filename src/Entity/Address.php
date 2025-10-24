<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true, name: 'house_no')]
    private ?string $houseNo = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true, name: 'subdivision')]
    private ?string $subdivision = null; // Keeping as is, but you could change to state/province if needed

    #[ORM\Column(length: 255)]
    private ?string $barangay = null;

    #[ORM\Column(length: 255, name: 'city_municipality')]
    private ?string $cityMunicipality = null;

    #[ORM\Column(length: 255, nullable: true, name: 'province')]
    private ?string $province = null;

    #[ORM\Column(length: 255, name: 'postal_code')]
    private ?string $postalCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @deprecated since it was renamed to customer.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getHouseNo(): ?string // In some regions, this might not be applicable.
    {
        return $this->houseNo;
    }

    public function setHouseNo(?string $houseNo): static
    {
        $this->houseNo = $houseNo;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getSubdivision(): ?string
    {
        return $this->subdivision;
    }

    public function setSubdivision(?string $subdivision): static
    {
        $this->subdivision = $subdivision;

        return $this;
    }

    public function getBarangay(): ?string
    {
        return $this->barangay;
    }

    public function setBarangay(string $barangay): static
    {
        $this->barangay = $barangay;

        return $this;
    }

    public function getCityMunicipality(): ?string
    {
        return $this->cityMunicipality;
    }

    public function setCityMunicipality(string $cityMunicipality): static
    {
        $this->cityMunicipality = $cityMunicipality;

        return $this;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): static
    {
        $this->province = $province;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = [
            $this->getHouseNo(),
            $this->getStreet(),
            $this->getSubdivision(),
            $this->getBarangay(),
            $this->getCityMunicipality(),
            $this->getProvince(),
            $this->getPostalCode(),
        ];
        return implode(', ', array_filter($parts));
    }
}
