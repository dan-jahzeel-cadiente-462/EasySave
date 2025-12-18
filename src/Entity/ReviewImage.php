<?php

namespace App\Entity;

use App\Repository\ReviewImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewImageRepository::class)]
class ReviewImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviewImages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Review $review = null;

    #[ORM\Column(length: 255)]
    private ?string $file_name = null;

    #[ORM\Column(nullable: true)]
    private ?int $sort_order = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $caption = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    public function setFileName(string $file_name): static
    {
        $this->file_name = $file_name;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function setSortOrder(?int $sort_order): static
    {
        $this->sort_order = $sort_order;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }
}
