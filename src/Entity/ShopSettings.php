<?php

namespace App\Entity;

use App\Repository\ShopSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopSettingsRepository::class)]
#[ORM\Table(name: 'shop_settings')]
class ShopSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $shopName = 'Maison Soussi';

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $tagline = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $heroTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $heroSubtitle = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column(length: 160)]
    private string $contactEmail = 'contact@maisonsoussi.com';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $contactAddress = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $tiktokUrl = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $whatsappUrl = null;

    /** Free shipping threshold in cents. */
    #[ORM\Column(type: 'integer')]
    private int $freeShippingThreshold = 20000;

    /** Flat shipping fee in cents. */
    #[ORM\Column(type: 'integer')]
    private int $shippingFee = 500;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $announcement = null;

    #[ORM\Column(type: 'integer')]
    private int $vatRate = 19;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopName(): string
    {
        return $this->shopName;
    }

    public function setShopName(string $shopName): static
    {
        $this->shopName = $shopName;

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): static
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getHeroTitle(): ?string
    {
        return $this->heroTitle;
    }

    public function setHeroTitle(?string $heroTitle): static
    {
        $this->heroTitle = $heroTitle;

        return $this;
    }

    public function getHeroSubtitle(): ?string
    {
        return $this->heroSubtitle;
    }

    public function setHeroSubtitle(?string $heroSubtitle): static
    {
        $this->heroSubtitle = $heroSubtitle;

        return $this;
    }

    public function getHeroImage(): ?string
    {
        return $this->heroImage;
    }

    public function setHeroImage(?string $heroImage): static
    {
        $this->heroImage = $heroImage;

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): static
    {
        $this->contactAddress = $contactAddress;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): static
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagramUrl;
    }

    public function setInstagramUrl(?string $instagramUrl): static
    {
        $this->instagramUrl = $instagramUrl;

        return $this;
    }

    public function getTiktokUrl(): ?string
    {
        return $this->tiktokUrl;
    }

    public function setTiktokUrl(?string $tiktokUrl): static
    {
        $this->tiktokUrl = $tiktokUrl;

        return $this;
    }

    public function getWhatsappUrl(): ?string
    {
        return $this->whatsappUrl;
    }

    public function setWhatsappUrl(?string $whatsappUrl): static
    {
        $this->whatsappUrl = $whatsappUrl;

        return $this;
    }

    public function getFreeShippingThreshold(): int
    {
        return $this->freeShippingThreshold;
    }

    public function setFreeShippingThreshold(int $threshold): static
    {
        $this->freeShippingThreshold = $threshold;

        return $this;
    }

    public function getShippingFee(): int
    {
        return $this->shippingFee;
    }

    public function setShippingFee(int $shippingFee): static
    {
        $this->shippingFee = $shippingFee;

        return $this;
    }

    public function getAnnouncement(): ?string
    {
        return $this->announcement;
    }

    public function setAnnouncement(?string $announcement): static
    {
        $this->announcement = $announcement;

        return $this;
    }

    public function getVatRate(): int
    {
        return $this->vatRate;
    }

    public function setVatRate(int $vatRate): static
    {
        $this->vatRate = $vatRate;

        return $this;
    }

    public function getVatAmount(int $total): int
    {
        if ($this->vatRate <= 0) {
            return 0;
        }

        return (int) round($total * $this->vatRate / (100 + $this->vatRate));
    }
}