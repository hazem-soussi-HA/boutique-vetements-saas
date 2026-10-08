<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ShopSettings;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    private const SIZES = ['XS', 'S', 'M', 'L', 'XL'];

    private readonly AsciiSlugger $slugger;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(DEMO_ADMIN_EMAIL)%')]
        private readonly string $adminEmail,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(DEMO_ADMIN_PASSWORD)%')]
        private readonly string $adminPassword,
    ) {
        $this->slugger = new AsciiSlugger();
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadSettings($manager);
        $this->loadUser($manager);

        $brands = $this->loadBrands($manager);
        $categories = $this->loadCategories($manager);
        $products = $this->loadProducts($manager, $categories, $brands);
        $this->loadOrders($manager, $products);
        $this->loadMessages($manager);
        $this->loadSubscribers($manager);

        $manager->flush();
    }

    private function loadSettings(ObjectManager $manager): void
    {
        $settings = new ShopSettings();
        $settings->setShopName('Maison Soussi');
        $settings->setTagline('L’élégance algérienne, sur mesure du quotidien');
        $settings->setHeroTitle('Des pièces qui racontent votre histoire');
        $settings->setHeroSubtitle("Notre nouvelle collection mêle tissue traditionnel et coupes contemporaines. Des matières choisies, des coupes pensées pour durer plus d'une saison.");
        $settings->setHeroImage('/images/hero.svg');
        $settings->setAnnouncement('Livraison offerte dès 20 000 DA • Retours gratuits sous 14 jours • Paiement à la livraison');
        $settings->setContactEmail('bonjour@maisonsoussi.dz');
        $settings->setContactPhone('+213 555 12 34 56');
        $settings->setContactAddress('12, rue Didouche Mourad, Alger Centre, Algérie');
        $settings->setFacebookUrl('https://facebook.com/maisonsoussi');
        $settings->setInstagramUrl('https://instagram.com/maisonsoussi');
        $settings->setTiktokUrl('https://tiktok.com/@maisonsoussi');
        $settings->setWhatsappUrl('https://wa.me/213555123456');
        $settings->setFreeShippingThreshold(20000);
        $settings->setShippingFee(600);
        $settings->setVatRate(19);

        $manager->persist($settings);
    }

    private function loadUser(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail($this->adminEmail);
        $user->setFullName('Hazem Soussi');
        $user->setPassword($this->hasher->hashPassword($user, $this->adminPassword));
        $user->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
    }

    /**
     * @return array<string, Brand>
     */
    private function loadBrands(ObjectManager $manager): array
    {
        $data = [
            ['Atelier Casbah', 'Algérie', 'Couture algéroise contemporaine, pièces en petites séries.'],
            ['Sahara Studio', 'Maroc', 'Laine du Sud et coupes berbères réinterprétées.'],
            ['Medina Chic', 'Tunisie', 'Soie et lin lavé, douceur méditerranéenne.'],
            ['Oran Tailoring', 'Algérie', 'Tailleur classique, de la coupe aux finitions.'],
        ];

        $brands = [];
        foreach ($data as [$name, $country, $description]) {
            $brand = new Brand();
            $brand->setName($name);
            $brand->setSlug($this->slugger->slug($name)->lower()->toString());
            $brand->setCountry($country);
            $brand->setDescription($description);
            $brand->setIsFeatured(true);
            $manager->persist($brand);
            $brands[$name] = $brand;
        }

        return $brands;
    }

    /**
     * @return array<string, Category>
     */
    private function loadCategories(ObjectManager $manager): array
    {
        $data = [
            ['Femme', 'Robes, tops et pièces du quotidien.', '👗', 1],
            ['Homme', 'Chemises, manteaux et accessoires.', '👔', 2],
            ['Robes de soirée', 'Soie, satin et finitions main.', '✨', 3],
            ['Vestes & Manteaux', 'Tricot structuré et laine doublée.', '🧥', 4],
            ['Accessoires', 'Foulards, sacs et bijoux dorés.', '👜', 5],
            ['Collection homme', 'Essentiels et smoking.', '🎩', 6],
        ];

        $categories = [];
        $position = 1;
        foreach ($data as [$name, $description, $icon, ]) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slugger->slug($name)->lower()->toString());
            $category->setDescription($description);
            $category->setIcon($icon);
            $category->setPosition($position++);
            $category->setIsActive(true);
            $manager->persist($category);
            $categories[$name] = $category;
        }

        return $categories;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, Brand>    $brands
     *
     * @return array<int, Product>
     */
    private function loadProducts(ObjectManager $manager, array $categories, array $brands): array
    {
        $rows = [
            ['Robe Nour en lin lavé', 'femme', 'Robes', 18900, 14900, ['XS', 'S', 'M', 'L'], ['Sable', 'Blanc cassé'], ['Lin', 'Coton'], 24, true, true, 'Atelier Casbah'],
            ['Robe Kenza brodée main', 'femme', 'Robes de soirée', 34000, null, ['S', 'M', 'L'], ['Noir', 'Bordeaux'], ['Soie', 'Velours'], 8, true, false, 'Medina Chic'],
            ['Blouse Yasmine en soie', 'femme', 'Robes', 12500, 9900, ['XS', 'S', 'M', 'L', 'XL'], ['Ivoire', 'Rose poudré'], ['Soie'], 31, false, true, 'Medina Chic'],
            ['Jupe plissée Amal', 'femme', 'Robes', 15800, null, ['S', 'M', 'L'], ['Kaki', 'Noir'], ['Twill'], 17, false, false, 'Atelier Casbah'],
            ['Ensemble zellige Oran', 'femme', 'Robes de soirée', 42000, 36500, ['S', 'M'], ['Émeraude'], ['Velours de soie'], 5, true, true, 'Atelier Casbah'],
            ['Pantalon djellaba chic', 'femme', 'Robes', 9800, null, ['S', 'M', 'L', 'XL'], ['Noir', 'Blanc'], ['Crêpe'], 40, false, false, 'Sahara Studio'],
            ['Veste trench Beïjaïa', 'femme', 'Vestes & Manteaux', 27500, 22900, ['S', 'M', 'L'], ['Camel', 'Noir'], ['Gabardine de coton'], 12, true, false, 'Oran Tailoring'],
            ['Manteau long Tlemcen', 'femme', 'Vestes & Manteaux', 38900, null, ['S', 'M', 'L'], ['Anthracite'], ['Laine mérinos'], 6, false, false, 'Sahara Studio'],
            ['Gilet Sid Ali', 'unisexe', 'Vestes & Manteaux', 16400, null, ['M', 'L', 'XL'], ['Écru', 'Indigo'], ['Lin mélangé'], 15, false, false, 'Atelier Casbah'],
            ['Chemise touareg', 'homme', 'Collection homme', 10900, 8900, ['M', 'L', 'XL'], ['Blanc', 'Bleu ciel'], ['Coton'], 28, false, true, 'Sahara Studio'],
            ['Costume Oran Smoking', 'homme', 'Collection homme', 45900, 39900, ['48', '50', '52'], ['Noir'], ['Laine Super 110s'], 4, true, false, 'Oran Tailoring'],
            ['Veste blazer Alger', 'homme', 'Collection homme', 21900, null, ['46', '48', '50'], ['Gris chiné', 'Marine'], ['Laine'], 11, false, false, 'Oran Tailoring'],
            ['Pantalon chino sahraoui', 'homme', 'Collection homme', 8900, null, ['42', '44', '46'], ['Sable'], ['Coton stretch'], 22, false, false, 'Atelier Casbah'],
            ['Scarf handi tissé', 'unisexe', 'Accessoires', 5900, 4500, ['TU'], ['Multi'], ['Laine'], 60, false, false, 'Medina Chic'],
            ['Sac cabas Bejaia', 'unisexe', 'Accessoires', 12900, null, ['TU'], ['Cuir noir', 'Cuir camel'], ['Cuir pleine fleur'], 14, false, true, 'Atelier Casbah'],
            ['Foulard Nedjma', 'unisexe', 'Accessoires', 3900, null, ['TU'], ['Corail', 'Ciel'], ['Soie'], 48, false, false, 'Sahara Studio'],
            ['Bracelet berbère argent', 'unisexe', 'Accessoires', 4900, null, ['TU'], ['Argent'], ['Argent 925'], 33, false, false, 'Medina Chic'],
            ['Ensemble sport Casbah', 'femme', 'Robes', 11200, null, ['S', 'M', 'L', 'XL'], ['Rose'], ['Molleton'], 26, false, true, 'Atelier Casbah'],
        ];

        $products = [];
        $position = 1;

        foreach ($rows as [$name, $gender, $categoryName, $price, $salePrice, $sizes, $colors, $materials, $stock, $featured, $isNew, $brandName]) {
            $product = new Product();
            $product->setName($name);
            $product->setSlug($this->slugger->slug($name)->lower()->toString());
            $product->setSku('MS-'.strtoupper(bin2hex(random_bytes(3))));
            $product->setPrice($price);
            $product->setSalePrice($salePrice);
            $product->setStock($stock);
            $product->setSizes($sizes);
            $product->setColors($colors);
            $product->setMaterials($materials);
            $product->setGender($gender);
            $product->setCategory($categories[$categoryName] ?? null);
            $product->setBrand($brands[$brandName] ?? null);
            $product->setPosition($position++);
            $product->setIsFeatured($featured);
            $product->setIsNew($isNew);
            // One archived product, so the "inactive products are not publicly
            // reachable" behaviour is covered by the demo data.
            $product->setIsActive('Ensemble sport Casbah' !== $name);
            $product->setShortDescription($this->shortDescription($name, $materials, $colors));
            $product->setDescription($this->longDescription($name, $materials, $colors, $categoryName));

            $imagePath = '/images/products/'.$this->slugger->slug($name)->lower()->toString().'.svg';
            $product->setMainImage($imagePath);

            $gallery = new ProductImage();
            $gallery->setUrl($imagePath);
            $gallery->setAlt($name);
            $gallery->setPosition(0);
            $product->addImage($gallery);

            $alt = new ProductImage();
            $alt->setUrl('/images/products/'.$this->slugger->slug($name)->lower()->toString().'-2.svg');
            $alt->setAlt($name.' - détail');
            $alt->setPosition(1);
            $product->addImage($alt);

            $createdAt = new \DateTimeImmutable('-'.\random_int(1, 45).' days');
            $product->setCreatedAt($createdAt);
            $product->setUpdatedAt($createdAt);

            $manager->persist($product);
            $products[] = $product;
        }

        return $products;
    }

    private function shortDescription(string $name, array $materials, array $colors): string
    {
        return sprintf(
            '%s, confectionnée en %s, disponible en %s.',
            $name,
            mb_strtolower(implode(' et ', $materials)),
            mb_strtolower(implode(', ', $colors))
        );
    }

    private function longDescription(string $name, array $materials, array $colors, string $categoryName): string
    {
        return implode("\n\n", [
            sprintf("%s est une pièce pensée pour durer. Nous sélectionnons des fibres auprès d'ateliers partenaires, et chaque exemplaire passe par un contrôle qualité avant expédition.", $name, mb_strtolower(implode(' / ', $materials))),
            sprintf('Coupe %s, finitions cousues main et patronage validé par nos stylistes. Disponible en %s selon disponibilités.', mb_strtolower($categoryName), mb_strtolower(implode(', ', $colors))),
            "Entretien : lavage à froid ou nettoyage à sec. Repassage à basse température. Repos sur cintre cintrable fourni.",
        ]);
    }

    /**
     * @param array<int, Product> $products
     */
    private function loadOrders(ObjectManager $manager, array $products): void
    {
        $customers = [
            ['Amina', 'Belkacem', 'amina.belkacem@exemple.dz', '+213 661 22 33 44', 'Alger', 'Alger'],
            ['Yacine', 'Haddad', 'y.haddad@exemple.dz', '+213 770 11 22 33', 'Oran', 'Oran'],
            ['Lina', 'Benaissa', 'lina.benaissa@exemple.dz', '+213 555 88 77 66', 'Constantine', 'Constantine'],
            ['Sofiane', 'Meziane', 's.meziane@exemple.dz', '+213 699 44 55 66', 'Blida', 'Blida'],
            ['Nawel', 'Cherif', 'nawel.cherif@exemple.dz', '+213 550 12 98 76', 'Tizi Ouzou', 'Tizi Ouzou'],
        ];

        $statuses = [
            Order::STATUS_DELIVERED, Order::STATUS_DELIVERED, Order::STATUS_DELIVERED,
            Order::STATUS_SHIPPED, Order::STATUS_SHIPPED, Order::STATUS_PAID,
            Order::STATUS_PENDING, Order::STATUS_PENDING,
        ];

        $orderCount = \random_int(9, 14);

        for ($i = 0; $i < $orderCount; ++$i) {
            $order = new Order();
            $order->setReference('MS-'.date('ymd', \random_int(0, 40)).'-'.strtoupper(bin2hex(random_bytes(3))));

            [$first, $last, $email, $phone, $city, $wilaya] = $customers[\random_int(0, count($customers) - 1)];
            $order->setCustomerFirstName($first);
            $order->setCustomerLastName($last);
            $order->setCustomerEmail($email);
            $order->setCustomerPhone($phone);
            $order->setShippingCity($city);
            $order->setShippingWilaya($wilaya);
            $order->setShippingAddress(\random_int(1, 200).', rue des Frères Bouadou, '.$city);
            $order->setPaymentMethod(0 === \random_int(0, 1) ? Order::PAYMENT_COD : Order::PAYMENT_BANK);
            $order->setStatus($statuses[\random_int(0, count($statuses) - 1)]);
            $order->setCreatedAt(new \DateTimeImmutable('-'.\random_int(1, 40).' days'));
            $order->setNotes(\random_int(0, 3) === 0 ? 'Appeler avant livraison.' : null);

            $subtotal = 0;
            $lineCount = \random_int(1, 3);
            $usedIndexes = [];

            for ($j = 0; $j < $lineCount; ++$j) {
                $index = \random_int(0, count($products) - 1);
                if (isset($usedIndexes[$index])) {
                    continue;
                }
                $usedIndexes[$index] = true;
                $product = $products[$index];

                $item = new OrderItem();
                $item->setProduct($product);
                $item->setProductName($product->getName());
                $item->setProductImage($product->getMainImage());
                $item->setSize($product->getSizes()[0] ?? null);
                $item->setColor($product->getColors()[0] ?? null);
                $item->setQuantity(\random_int(1, 2));
                $item->setUnitPrice($product->getEffectivePrice());
                $order->addItem($item);

                $subtotal += $item->getTotal();
            }

            $shipping = $subtotal >= 20000 ? 0 : 600;
            $order->setSubtotal($subtotal);
            $order->setShipping($shipping);
            $order->setTotal($subtotal + $shipping);

            $manager->persist($order);
        }
    }

    private function loadMessages(ObjectManager $manager): void
    {
        $rows = [
            ['Karim Belhadj', 'karim.belhadj@exemple.dz', '+213 771 00 11 22', 'Question sur les tailles', 'Bonjour, je fais 1m78 et je suis entre M et L, la robe Nour en lin lavé, me conseillez-vous S ou M ?', true],
            ['Nadia Brahimi', 'nadia.brahimi@exemple.dz', null, 'Délai de livraison', 'Bonjour, pouvez-vous me confirmer le délai pour une livraison à Sétif ? Merci.', false],
            ['Riad Saidi', 'riad.saidi@exemple.dz', '+213 550 88 77 66', 'Commande personnalisée', 'Je cherche une tenue complète pour un mariage. Un devis avec prise de mesures personnalisées serait possible.', false],
            ['Meriem Toumi', 'meriem.toumi@exemple.dz', null, 'Retour produit', 'Comment procéder pour un échange de taille ? J’ai acheté la blouse Yasmine en M, je l’ai reçue aujourd’hui.', false],
            ['Hichem Larbi', 'h.larbi@exemple.dz', '+213 699 45 78 90', 'Conseil style', 'Je cherche un costume pour un entretien, le smoking Oran est-il disponible en taille 52 ?', false],
            ['Sonia Bouazza', 's.bouazza@exemple.dz', null, 'Colisage', 'Le colisage est-il fait avec du papier kraft?', true],
        ];

        foreach ($rows as [$name, $email, $phone, $subject, $message, $isRead]) {
            $entity = new ContactMessage();
            $entity->setFullName($name);
            $entity->setEmail($email);
            $entity->setPhone($phone);
            $entity->setSubject($subject);
            $entity->setMessage($message);
            $entity->setIsRead($isRead);
            $entity->setCreatedAt(new \DateTimeImmutable('-'.\random_int(1, 20).' days'));
            $manager->persist($entity);
        }
    }

    private function loadSubscribers(ObjectManager $manager): void
    {
        $emails = [
            'amina.belkacem@exemple.dz', 'y.haddad@exemple.dz', 'lina.benaissa@exemple.dz',
            's.meziane@exemple.dz', 'nawel.cherif@exemple.dz', 'karim.belhadj@exemple.dz',
            'nadia.brahimi@exemple.dz', 'riad.saidi@exemple.dz', 'meriem.toumi@exemple.dz',
            'hichem.larbi@exemple.dz', 'sonia.bouazza@exemple.dz', 'w.mansouri@exemple.dz',
            'f.zerrouki@exemple.dz', 'a.khelifi@exemple.dz',
        ];

        foreach ($emails as $email) {
            $subscriber = new \App\Entity\NewsletterSubscriber();
            $subscriber->setEmail($email);
            $subscriber->setIsActive(true);
            $subscriber->setSubscribedAt(new \DateTimeImmutable('-'.\random_int(1, 90).' days'));
            $manager->persist($subscriber);
        }
    }
}