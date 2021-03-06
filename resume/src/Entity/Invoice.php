<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Validator\InvoiceTax;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 * @UniqueEntity("number")
 */
class Invoice
{
    /**
     * @ORM\Id()', default: true , role: ROLE_USER_LIST }
      - { entity: 'UsersManagement', label: 'Members management', icon: 'user' , role: ROLE_USER_ALL }
      - { label: 'Meetings' }
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $number;

    const NUMBER_DATE_FORMAT = 'Ym-';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Experience", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $experience;

    /**
     * @ORM\Column(type="date")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $payedAt;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalHt;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalTax;

    const TAX_MULTIPLIER = 0.2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $object;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $tjm;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payedBy;

    const PAYEDBY_CHECK    = "check";
    const PAYEDBY_TRANSFERT = "transfert";

    /** @var array user friendly named type */
    const PAYED_BYS = [
        'Check' => self::PAYEDBY_CHECK,
        'Transfert' => self::PAYEDBY_TRANSFERT,
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_WAITING = 'waiting';
    const STATUS_PAYED = 'payed';

    /**
     * @ORM\Column(type="string", length=255, nullable=true))
     */
    private $status;

    /** @var array user friendly named type */
    const STATUES = [
        'Draft' => self::STATUS_DRAFT,
        'Waiting' => self::STATUS_WAITING,
        'Payed' => self::STATUS_PAYED,
    ];

    const DUE_INTERVAL_1M = 'P1M';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dueInterval;

    /** @var array user friendly named type */
    const DUE_INTERVALES = [
        '30 days end of month' => self::DUE_INTERVAL_1M,
    ];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Activity", mappedBy="invoice", cascade={"persist", "remove"})
     */
    private $activities;

    /**
     * @var Period
     * @ORM\ManyToOne(targetEntity="App\Entity\Period", inversedBy="invoices")
     */
    private $period;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=1, nullable=true)
     */
    private $daysCount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $extraLibelle;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $extraHt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference;

    const TJM_DEFAULT = 400;
    const LIMIT_AE_TVA = 33200;
    const LIMIT_AE = 70000;

    public function __construct()
    {
        $this->daysCount = 0;
        $this->tjm = self::TJM_DEFAULT;
        $this->createdAt = new \DateTime();
        $this->object = '';
        $this->setNumber((new \DateTime())->format(Invoice::NUMBER_DATE_FORMAT));
        $this->setPayedBy(self::PAYEDBY_TRANSFERT);
        $this->status = self::STATUS_DRAFT;
        $this->dueInterval = 'P1M';
        $this->activities = new ArrayCollection();
    }

    public function updateHt(): void
    {
        $this->totalHt = $this->tjm * $this->daysCount + $this->extraHt;
    }

    public function isCredit(): bool
    {
        return $this->getTotalHt() < 0;
    }

    public function isEditable(): bool
    {
        return !$this->getPayedAt() && $this->getStatus() === self::STATUS_DRAFT;
    }

    public function getTotalNet(): float
    {
        return floatval($this->totalHt) * (1 - Declaration::SOCIAL_NON_COMMERCIALE);
    }

    /**
     * @param Activity[] $activities
     */
    public function importActivities(array $activities)
    {
        $dayCount = 0;

        foreach ($activities as $activity) {
            $activity->setInvoice($this);
            $dayCount += $activity->getValue();
        }

        $this->setDaysCount($dayCount);
    }

    public function getFilename(): string
    {
        return $this->getNumber().'.pdf';
    }

    public function __toString(): string
    {
        return $this->getNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getExperience(): ?Experience
    {
        return $this->experience;
    }

    public function getExperienceName(): string
    {
        return $this->experience ? $this->getExperience()->__toString() : '';
    }

    public function setExperience(?Experience $experience): self
    {
        $this->experience = $experience;

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

    public function getPayedAt(): ?\DateTimeInterface
    {
        return $this->payedAt;
    }

    public function setPayedAt(?\DateTimeInterface $payedAt): self
    {
        $this->payedAt = $payedAt;

        return $this;
    }

    public function getCreatedAtYear(): int
    {
        return $this->createdAt->format('Y');
    }

    public function getPayedAtYear(): int
    {
        return $this->payedAt->format('Y');
    }

    public function getCreatedAtQuarter(): int
    {
        return ceil($this->createdAt->format('n') / 3);
    }

    public function getPayedAtQuarter(): int
    {
        return ceil($this->payedAt->format('n') / 3);
    }

    public function getTotalHt(): ?string
    {
        return $this->totalHt;
    }

    public function setTotalHt(?string $totalHt): self
    {
        $this->totalHt = $totalHt;
        if (!$this->getDaysCount() && $this->getTjm()) {
            $this->setDaysCount($this->getTotalHt() / $this->getTjm());
        }

        return $this;
    }

    public function getTotalTax(): ?string
    {
        return $this->totalTax;
    }

    public function setTotalTax(?string $totalTax): self
    {
        $this->totalTax = $totalTax;

        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->getTotalHt() + $this->getTotalTax();
    }

    public function getObject(): ?string
    {
        return $this->object;
    }

    public function setObject(?string $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getTjm(): ?int
    {
        return $this->tjm;
    }

    public function setTjm(?int $tjm): self
    {
        $this->tjm = $tjm;

        return $this;
    }

    public function getPayedBy(): ?string
    {
        return $this->payedBy;
    }

    public function setPayedBy(?string $payedBy): self
    {
        $this->payedBy = $payedBy;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayedByName()
    {
        $payedByName = array_flip(self::PAYED_BYS);
        if (!isset($payedByName[$this->payedBy])) {
            return null;
        }

        return $payedByName[$this->payedBy];
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        $statusName = array_flip(self::STATUES);
        if (!isset($statusName[$this->status])) {
            return null;
        }

        return $statusName[$this->status];
    }

    public function getDueInterval(): ?string
    {
        return $this->dueInterval;
    }

    public function setDueInterval(?string $dueInterval): self
    {
        $this->dueInterval = $dueInterval;

        return $this;
    }

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getDueAt(): ?\DateTime
    {
        if (!$this->getCreatedAt() || !$this->getDueInterval()) return null;

        $createdAt = $this->getCreatedAt();
        $lastDayOfMonth = new \DateTime($createdAt->format('Y-m-t'));
        $firstDayOfNextMonth = (clone $lastDayOfMonth)->add(new \DateInterval('P1D'));
        return new \DateTime($firstDayOfNextMonth->format('Y-m-t'));
    }

    /**
     * @return Collection|Activity[]
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): self
    {
        if (!$this->activities->contains($activity)) {
            $this->activities[] = $activity;
            $activity->setInvoice($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): self
    {
        if ($this->activities->contains($activity)) {
            $this->activities->removeElement($activity);
            // set the owning side to null (unless already changed)
            if ($activity->getInvoice() === $this) {
                $activity->setInvoice(null);
            }
        }

        return $this;
    }

    public function getPeriod(): ?Period
    {
        return $this->period;
    }

    public function getPeriodName(): string
    {
        return $this->period ? $this->period->__toString() : '';
    }

    public function setPeriod(?Period $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getSocialDeclaration(): ?Declaration
    {
        if ($this->getPeriod()) {
            $declarations = $this->getPeriod()->getDeclarations();
            foreach ($declarations as $declaration) {
                if ($declaration->getType() === Declaration::TYPE_SOCIAL) {
                    return $declaration;
                }
            }
        }

        return null;
    }

    public function getDaysCount(): ?float
    {
        return $this->daysCount;
    }

    public function setDaysCount(?float $daysCount): self
    {
        $this->daysCount = $daysCount;
        if (!$this->getTotalHt()) {
            $this->setTotalHt($this->getDaysCount() * $this->getTjm());
        }

        return $this;
    }

    public function getExtraLibelle(): ?string
    {
        return $this->extraLibelle;
    }

    public function setExtraLibelle(?string $extraLibelle): self
    {
        $this->extraLibelle = $extraLibelle;

        return $this;
    }

    public function getExtraHt(): ?string
    {
        return $this->extraHt;
    }

    public function setExtraHt(?string $extraHt): self
    {
        $this->extraHt = $extraHt;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }
}
