<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
#[ORM\Index(name: 'idx_product_slug', columns: ['slug'])]
class Product
{
    public const GENDER_ALL = 'unisexe';
    public const GENDERS = ['femme', 'homme', 'unisexe', 'enfant'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    private string $name = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 40)]
    private string $sku = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Price in cents. */
    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    /** Discounted price in cents, nullable. */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salePrice = null;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainImage = null;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    private Collection $images;

    /** @var array<int, string> */
    #[ORM\Column(type: 'json')]
    private array $sizes = [];

    /** @var array<int, string> */
    #[ORM\Column(type: 'json')]
    private array $colors = [];

    /** @var array<int, string> */
    #[ORM\Column(type: 'json')]
    private array $materials = [];

    #[ORM\Column(length: 20)]
    private string $gender = self::GENDER_ALL;

    #[ORM\Column(length: 20)]
    private string $condition = 'neuf';

    #[ORM\Column(options: ['default' => false])]
    private bool $isFeatured = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isNew = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brand = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSalePrice(): ?int
    {
        return $this->salePrice;
    }

    public function setSalePrice(?int $salePrice): static
    {
        $this->salePrice = $salePrice;

        return $this;
    }

    public function getEffectivePrice(): int
    {
        if ($this->salePrice !== null && $this->salePrice > 0 && $this->salePrice < $this->price) {
            return $this->salePrice;
        }

        return $this->price;
    }

    public function getDiscountPercent(): int
    {
        if ($this->salePrice === null || $this->price <= 0) {
            return 0;
        }

        $diff = $this->price - $this->salePrice;
        if ($diff <= 0) {
            return 0;
        }

        return (int) round(($diff / $this->price) * 100);
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getMainImage(): ?string
    {
        return $this->mainImage;
    }

    public function setMainImage(?string $mainImage): static
    {
        $this->mainImage = $mainImage;

        return $this;
    }

    public function getGallery(): array
    {
        $urls = [];
        foreach ($this->images as $image) {
            $urls[] = $image->getUrl();
        }

        if ([] === $urls && $this->mainImage) {
            $urls[] = $this->mainImage;
        }

        return $urls;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        $this->images->removeElement($image);

        return $this;
    }

    /** @return array<int, string> */
    public function getSizes(): array
    {
        return $this->sizes;
    }

    /** @param array<int, string> $sizes */
    public function setSizes(array $sizes): static
    {
        $this->sizes = array_values($sizes);

        return $this;
    }

    /** @return array<int, string> */
    public function getColors(): array
    {
        return $this->colors;
    }

    /** @param array<int, string> $colors */
    public function setColors(array $colors): static
    {
        $this->colors = array_values($colors);

        return $this;
    }

    /** @return array<int, string> */
    public function getMaterials(): array
    {
        return $this->materials;
    }

    /** @param array<int, string> $materials */
    public function setMaterials(array $materials): static
    {
        $this->materials = array_values($materials);

        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = \in_array($gender, self::GENDERS, true) ? $gender : self::GENDER_ALL;

        return $this;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew): static
    {
        $this->isNew = $isNew;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 5;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}