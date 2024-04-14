<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WorkshopSubscribeRepository")
 * @ORM\EntityListeners({"App\EventListener\WorkshopSubscribeListener"})
 */
class WorkshopSubscribe
{
    const STATUS_SUBSCRIBED     = 1;
    const STATUS_PRE_SUBSCRIBE  = 0;
    const STATUS_WAITING_SEATS  = -1;
    const STATUS_SUB_NOT_CONFIRMED  = -2;
    const STATUS_WAITING_VALIDATION = -3;
    const STATUS_WAITING_SEATS_STUCK = -4;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $subscribeDate;

    /**
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="subscribes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserVSI", inversedBy="subscribes")
     * @ORM\JoinColumn(nullable=true)
     */
    private $userVSI;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Workshop", inversedBy="subscribes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $workshop;

    /**
     * @ORM\Column(type="boolean")
     */
    private $has_come;



    //
    // WorkshopSubscribe Methods
    //

    public function __construct()
    {
        $this->subscribeDate  = new \DateTime();
        $this->has_come       = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSubscribeDate(): ?\DateTimeInterface
    {
        return $this->subscribeDate;
    }

    public function setSubscribeDate(\DateTimeInterface $subscribeDate): self
    {
        $this->subscribeDate = $subscribeDate;

        return $this;
    }

    public function getStatus(): ?int
    {
        // Pre-subers OR Waiting user validation
        if($this->status == self::STATUS_PRE_SUBSCRIBE || $this->status == self::STATUS_WAITING_VALIDATION) {
            // Old workshop
            if(!$this->workshop->isOpen()) {
                $this->status = self::STATUS_SUB_NOT_CONFIRMED;
            } else if($this->workshop->isWaitingSubscribesValidation()) {
                $this->status = self::STATUS_WAITING_VALIDATION;
            }
        }

        // Waiters
        if($this->status == self::STATUS_WAITING_SEATS) {
            // Old workshop : user stuck in wait for seats
            if(!$this->workshop->isOpen()) {
                $this->status = self::STATUS_WAITING_SEATS_STUCK;
            }
        }


        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusSlug()
    {
        $statusSlugs = array(
          self::STATUS_SUB_NOT_CONFIRMED    => 'sub-not-confirmed',
          self::STATUS_WAITING_SEATS        => 'waiting-seats',
          self::STATUS_PRE_SUBSCRIBE        => 'pre-subscribe',
          self::STATUS_WAITING_VALIDATION   => 'pre-subscribe', // Only text change not slugs, TODO change it later > need to update JS and CSS
          self::STATUS_SUBSCRIBED           => 'subscribed',
          self::STATUS_WAITING_SEATS_STUCK  => 'waiting-stuck');

        $status = $this->getStatus();
        return isset($statusSlugs[$status]) ? $statusSlugs[$status] : false;
    }

    public function getStatusText()
    {
        $statusTexts = array(
          self::STATUS_SUB_NOT_CONFIRMED    => 'Inscription non-confirmée',
          self::STATUS_WAITING_SEATS        => 'En file d\'attente',
          self::STATUS_PRE_SUBSCRIBE        => 'Inscrit&middot;e',
          self::STATUS_WAITING_VALIDATION   => 'En attente de confirmation',
          self::STATUS_SUBSCRIBED           => 'Inscription confirmée',
          self::STATUS_WAITING_SEATS_STUCK  => 'En file d\'attente');

        $status = $this->getStatus();
        return isset($statusTexts[$status]) ? $statusTexts[$status] : false;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserVSI(): ?UserVSI
    {
        return $this->userVSI;
    }

    public function setUserVSI(?UserVSI $userVSI): self
    {
        $this->userVSI = $userVSI;

        return $this;
    }

    public function getWorkshop(): ?Workshop
    {
        return $this->workshop;
    }

    public function setWorkshop(?Workshop $workshop): self
    {
        $this->workshop = $workshop;

        return $this;
    }

    public function getHasCome(): ?bool
    {
        return $this->has_come;
    }

    public function setHasCome(bool $has_come): self
    {
        $this->has_come = $has_come;

        return $this;
    }

    public function getHasComeText(): string
    {
        return ($this->has_come == true ? 'Présence validée' : 'Présence non-validée');
    }
}
