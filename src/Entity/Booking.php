<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookingRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Booking
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="bookings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $booker;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ad", inversedBy="bookings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ad;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Type("DateTime", message="Attention, le format renseigné n'est pas valide !")
     * @Assert\GreaterThan("today", message="La date d'arrivée doit être ultérieur à la date d'aujourd'hui", groups={"Front"})
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Type("DateTime", message="Attention, le format renseigné n'est pas valide !")
     * @Assert\GreaterThan(propertyPath="startDate", message="La date de départ doit être supérieur à la date d'arrivée")
     */
    private $endDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="integer")
     */
    private $ttc_amount;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PromoCode", inversedBy="booking")
     */
    private $promoCode;

    /**
     * Appelé à chaque création de réservation // Calcul le montant et initialise la date de création
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist()
    {
        if (empty($this->createdAt)){
            $this->createdAt = new \DateTime();
        }
        if (empty($this->amount)){
            $this->amount = $this->ad->getPrice() * $this->getDuration();
        }

        if (empty($this->ttc_amount)){
            $this->ttc_amount = (($this->amount * 0.03) + $this->amount);
        }
    }

    public function getDuration()
    {
        $difference = $this->endDate->diff($this->startDate);

        return $difference->days;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooker(): ?User
    {
        return $this->booker;
    }

    public function setBooker(?User $booker): self
    {
        $this->booker = $booker;

        return $this;
    }

    public function getAd(): ?Ad
    {
        return $this->ad;
    }

    public function setAd(?Ad $ad): self
    {
        $this->ad = $ad;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

// Afficher le prix avec promocode

    public function promoAmount()
    {
        $promoCode = $this->getPromoCode();
        if($promoCode){
            if ($promoCode->getType() === 'POURCENTAGE'){
                $result = $this->ttc_amount - ($this->ttc_amount * $promoCode->getAmount() / 100);
            }else{
                $result = $this->ttc_amount - $promoCode->getAmount();
            }
        }
        return $result;
    }

    public function promoOperator()
    {
        $promoCode = $this->getPromoCode();
        if($promoCode){
            if ($promoCode->getType() === 'POURCENTAGE'){
                $operator = '%';
            }else{
                $operator = '€';
            }
        }
        return $operator;

    }

// FONCTIONS MANIPULATION DE DATE POUR LES RESERVATIONS
    public function isBookableDates()
    {
        // Dates impossible pour l'annonce
        $notAvailableDays = $this->ad->getNotAvailableDays();

        // On compare les dates choisies avec les dates impossibles
        $bookingDays = $this->getDays();

        $formatDay = function ($day) {
            return $day->format('Y-m-d');
        };
        // Tableau des journées en string
        $days = array_map($formatDay, $bookingDays);
        $notAvailable = array_map($formatDay, $notAvailableDays);

        foreach ($days as $day){
            if (array_search($day, $notAvailable) !== false) return false;
        }
        return true;
    }

    /**
     * Permet de récupérer un tableau des journéees qui correspondes à la réservation
     *
     * @return \DateTime[]
     */
    public function getDays()
    {
        $resultat = range(
            $this->startDate->getTimestamp(),
            $this->endDate->getTimestamp(),
            24*60*60
        );
        $days = array_map(function ($dayTimestamp){
            return new \DateTime(date('Y-m-d', $dayTimestamp));
        }, $resultat);

        return $days;
    }

    public function getTtcAmount(): ?float
    {
        return $this->ttc_amount;
    }

    public function setTtcAmount(float $ttc_amount): self
    {
        $this->ttc_amount = $ttc_amount;

        return $this;
    }

    public function getPromoCode(): ?PromoCode
    {
        return $this->promoCode;
    }

    public function setPromoCode(?PromoCode $promoCode): self
    {
        $this->promoCode = $promoCode;

        return $this;
    }
}
