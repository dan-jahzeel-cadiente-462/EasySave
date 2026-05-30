<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $rating = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @var Collection<int, ReviewImage>
     */
    #[ORM\OneToMany(targetEntity: ReviewImage::class, mappedBy: 'review')]
    private Collection $reviewImages;

    #[ORM\Column(length: 255)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->reviewImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return Collection<int, ReviewImage>
     */
    public function getReviewImages(): Collection
    {
        return $this->reviewImages;
    }

    public function addReviewImage(ReviewImage $reviewImage): static
    {
        if (!$this->reviewImages->contains($reviewImage)) {
            $this->reviewImages->add($reviewImage);
            $reviewImage->setReview($this);
        }

        return $this;
    }

    public function removeReviewImage(ReviewImage $reviewImage): static
    {
        if ($this->reviewImages->removeElement($reviewImage)) {
            // set the owning side to null (unless already changed)
            if ($reviewImage->getReview() === $this) {
                $reviewImage->setReview(null);
            }
        }

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    
}
