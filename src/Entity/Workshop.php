<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WorkshopRepository")
 * @ORM\EntityListeners({"App\EventListener\WorkshopListener"})
 */
class Workshop
{
    const DAYS_BEFORE_STOPPING_SUBSCRIBE = 3;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $date_start;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $date_end;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\WorkshopTheme", inversedBy="workshops")
     * @ORM\JoinColumn(nullable=true)
     */
    private $theme;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\NotBlank()
     */
    private $nb_seats;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\WorkshopSubscribe", mappedBy="workshop", orphanRemoval=true)
     * @ORM\OrderBy({"status" = "DESC", "subscribeDate" = "ASC"})
     */
    private $subscribes;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="workshops")
     * @ORM\JoinColumn(nullable=false)
     */
    private $lecturer;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Address", inversedBy="workshops")
     */
    private $address;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_VSI_type;

    public function __construct()
    {
        $this->subscribes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->date_start;
    }

    public function setDateStart(\DateTimeInterface $date_start): self
    {
        $this->date_start = $date_start;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->date_end;
    }

    public function setDateEnd(\DateTimeInterface $date_end): self
    {
        $this->date_end = $date_end;

        return $this;
    }

    public function getTheme(): ?WorkshopTheme
    {
        return $this->theme;
    }

    public function setTheme(?WorkshopTheme $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getNbSeats(): ?int
    {
        return $this->nb_seats;
    }

    public function setNbSeats(int $nb_seats): self
    {
        $this->nb_seats = $nb_seats;

        return $this;
    }

    public function getNbSeatsLeft()
    {
        // return $this->getNbSeats() - count($this->getSubscribes());
        return $this->getNbSeats() - $this->getNbSeatsTaken();
    }

    public function getNbSeatsTaken()
    {
        return count($this->getSubscribesByStatus(array(
            WorkshopSubscribe::STATUS_SUBSCRIBED,
            WorkshopSubscribe::STATUS_PRE_SUBSCRIBE,
            WorkshopSubscribe::STATUS_SUB_NOT_CONFIRMED,
            WorkshopSubscribe::STATUS_WAITING_VALIDATION,
        )));
    }

    public function getNbWaiters()
    {
        return count($this->getSubscribesByStatus(array(
            WorkshopSubscribe::STATUS_WAITING_SEATS,
            WorkshopSubscribe::STATUS_WAITING_SEATS_STUCK,
        )));
    }

    public function hasSeatsLeft()
    {
        $now = new \DateTime();
        return ($this->getNbSeatsLeft() > 0 && $this->getDateStart() > $now);
    }

    public function getStatusSlug()
    {
        if($this->isWaitingSubscribesValidation())
          return 'waiting-validation';

        if($this->isAvailableForSubscribe())
          return 'subscribes-opened';

        return 'subscribes-closed';
    }

    public function getStatusText()
    {
        if($this->isWaitingSubscribesValidation())
          return 'Confirmation';

        if($this->isAvailableForSubscribe())
          return 'Inscription';

        return 'Fermeture';
    }

    /**
     * @return Collection|WorkshopSubscribe[]
     */
    public function getSubscribes(): Collection
    {
        return $this->subscribes;
    }

    /**
     * @return Collection|WorkshopSubscribe[]
     */
    public function getSubscribeByUser(User $user)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('user', $user));

        $matches = $this->subscribes->matching($criteria);

        return (count($matches) > 0) ? $matches[0] : null;
    }

    /**
     * @return Collection|WorkshopSubscribe[]
     */
    public function getSubscribeByUserVSI(UserVSI $user_vsi)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('userVSI', $user_vsi));

        $matches = $this->subscribes->matching($criteria);

        return (count($matches) > 0) ? $matches[0] : null;
    }

    /**
     * @return Collection|WorkshopSubscribe[]
     */
    public function getSubscribesByStatus($status, $limit = null)
    {
        $criteria = Criteria::create();

        if(is_array($status)) {
            foreach ($status as $status_value) {
                $criteria->orWhere(Criteria::expr()->eq('status', $status_value));
            }
        } else {
            $criteria->where(Criteria::expr()->eq('status', $status));
        }

        if($limit != null && intval($limit) > 0)
          $criteria->setMaxResults(intval($limit));

        return $this->subscribes->matching($criteria);
    }

    public function addSubscribe(WorkshopSubscribe $subscribe): self
    {
        if (!$this->subscribes->contains($subscribe)) {
            $this->subscribes[] = $subscribe;
            $subscribe->setWorkshop($this);
        }

        return $this;
    }

    public function removeSubscribe(WorkshopSubscribe $subscribe): self
    {
        if ($this->subscribes->contains($subscribe)) {
            $this->subscribes->removeElement($subscribe);
            // set the owning side to null (unless already changed)
            if ($subscribe->getWorkshop() === $this) {
                $subscribe->setWorkshop(null);
            }
        }

        return $this;
    }

    public function getSubscribesMailingList($status = null)
    {
        $subbers = array();

        if(count($this->subscribes) > 0) {
            foreach ($this->subscribes as $subscribe) {
                $user = (!is_null($subscribe->getUserVSI()) ? $subscribe->getUserVSI() : $subscribe->getUser());
                $subbers = array_merge($subbers, $user->getMailingFormat());
            }
        }

        return $subbers;
    }

    public function getDateBeforeStoppingSubscribe()
    {
        $last_date_to_subscribe = clone $this->date_start;
        $last_date_to_subscribe->sub(new \DateInterval('P'.self::DAYS_BEFORE_STOPPING_SUBSCRIBE.'D'));
        return $last_date_to_subscribe;
    }

    public function isOpen()
    {
        return $this->isAvailableForSubscribe() || $this->isWaitingSubscribesValidation();
    }

    public function isAvailableForSubscribe()
    {
        $now = new \DateTime();

        // ? ... meh ~
        // $now->setTime(23, 59, 59);

        return ($this->getDateStart() > $now);
    }

    public function isWaitingSubscribesValidation()
    {
        $now = new \DateTime();

        $dateStartWaiting = $this->getDateBeforeStoppingSubscribe();

        // Reset dateStart time : a workshop is in waiting subscribes validation no matter what time it is
        $dateStartWaiting->setTime(0, 0, 0);
        // $now->setTime(23, 59, 59);

        // If NOW() is between workshop's (date_start - 3 days) AND (date_start)
        return ($dateStartWaiting < $now && $this->getDateStart() > $now);
    }

    // TODO : to test
    public function isAvailableForWaitingLine()
    {
        return $this->isWaitingSubscribesValidation() == false && $this->hasSeatsLeft() == false;
    }

    public function getLecturer(): ?User
    {
        return $this->lecturer;
    }

    public function setLecturer(?User $lecturer): self
    {
        $this->lecturer = $lecturer;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getIsVSIType(): ?bool
    {
        return $this->is_VSI_type;
    }

    public function setIsVSIType(?bool $is_VSI_type): self
    {
        $this->is_VSI_type = $is_VSI_type;

        return $this;
    }
}
