<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_PAID = 'payee';
    public const STATUS_SHIPPED = 'expediee';
    public const STATUS_DELIVERED = 'livree';
    public const STATUS_CANCELLED = 'annulee';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PAID => 'Payée',
        self::STATUS_SHIPPED => 'Expédiée',
        self::STATUS_DELIVERED => 'Livrée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const PAYMENT_COD = 'paiement_a_la_livraison';
    public const PAYMENT_BANK = 'virement_bancaire';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $reference = '';

    #[ORM\Column(length: 80)]
    private string $customerFirstName = '';

    #[ORM\Column(length: 80)]
    private string $customerLastName = '';

    #[ORM\Column(length: 160)]
    private string $customerEmail = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 120)]
    private string $shippingCity = '';

    #[ORM\Column(length: 200)]
    private string $shippingAddress = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingWilaya = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 40)]
    private string $paymentMethod = self::PAYMENT_COD;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    /** Subtotal in cents. */
    #[ORM\Column(type: 'integer')]
    private int $subtotal = 0;

    /** Shipping fee in cents. */
    #[ORM\Column(type: 'integer')]
    private int $shipping = 0;

    /** Total in cents. */
    #[ORM\Column(type: 'integer')]
    private int $total = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $couponCode = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => \SortDirection::Ascending])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCustomerFirstName(): string
    {
        return $this->customerFirstName;
    }

    public function setCustomerFirstName(string $firstName): static
    {
        $this->customerFirstName = $firstName;

        return $this;
    }

    public function getCustomerLastName(): string
    {
        return $this->customerLastName;
    }

    public function setCustomerLastName(string $lastName): static
    {
        $this->customerLastName = $lastName;

        return $this;
    }

    public function getCustomerFullName(): string
    {
        return trim($this->customerFirstName.' '.$this->customerLastName);
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $email): static
    {
        $this->customerEmail = $email;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $phone): static
    {
        $this->customerPhone = $phone;

        return $this;
    }

    public function getShippingCity(): string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(string $city): static
    {
        $this->shippingCity = $city;

        return $this;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $address): static
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function getShippingWilaya(): ?string
    {
        return $this->shippingWilaya;
    }

    public function setShippingWilaya(?string $wilaya): static
    {
        $this->shippingWilaya = $wilaya;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentLabel(): string
    {
        return self::PAYMENT_COD === $this->paymentMethod ? 'Paiement à la livraison' : 'Virement bancaire';
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = isset(self::STATUSES[$status]) ? $status : self::STATUS_PENDING;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getShipping(): int
    {
        return $this->shipping;
    }

    public function setShipping(int $shipping): static
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): static
    {
        $this->couponCode = $couponCode;

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

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }
}