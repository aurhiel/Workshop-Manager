<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\EntityListeners({"App\EventListener\UserListener"})
 */
class User implements AdvancedUserInterface, \Serializable
{
    const SERVICE_TYPE_ACTIV_CREA   = 'activ-crea';

    const SERVICE_TYPE_ACTIV_PROJET = 'activ-projet';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="is_archived", type="boolean")
     */
    private $isArchived = false;

    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = array();

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $registerDate;

    /**
     * @ORM\Column(type="date")
     * @Assert\Date()
     */
    private $registerEndDate;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank()
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\Length(
     *      min = 8,
     *      max = 8,
     *      exactMessage = "Votre ID pôle emploi doit faire exactement {{ limit }} caractères"
     * )
     */
    private $idPoleEmploi;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $serviceType;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * The below length depends on the "algorithm" you use for encoding
     * the password, but this works well with bcrypt.
     *
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\WorkshopSubscribe", mappedBy="user", orphanRemoval=true)
     */
    private $subscribes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Workshop", mappedBy="lecturer")
     */
    private $workshops;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hideHelpModal;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ResetPassword", mappedBy="user", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $resetPassword;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isConsultant;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $referentConsultant;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $askReactivationExpiresAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserVSI", mappedBy="referentConsultant")
     */
    private $userVSIs;


    public function __construct()
    {
        $this->isActive = false;
        // TODO add the user's Timezone, instead of doing it into twig
        $this->registerDate = new \DateTime();
        $this->subscribes = new ArrayCollection();
        $this->workshops  = new ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid('', true));

        $this->hideHelpModal = false;
        $this->userVSIs = new ArrayCollection();
    }


    public function getId()
    {
        return $this->id;
    }


    // Register date (= now())
    public function getRegisterDate()
    {
        return $this->registerDate;
    }


    // Register end date
    public function getRegisterEndDate(): ?\DateTimeInterface
    {
        return $this->registerEndDate;
    }

    public function setRegisterEndDate(\DateTimeInterface $registerEndDate): self
    {
        $this->registerEndDate = $registerEndDate;

        return $this;
    }


    // Email
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getMailingFormat()
    {
        return array($this->getEmail() => $this->getFirstname().' '.$this->getLastname());
    }


    // Username
    public function getUsername()
    {
        return $this->email;
    }

    // public function setUsername($username)
    // {
    //     $this->username = $username;
    // }


    // Firstname
    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }


    // Lastname
    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }


    // Phone
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }


    // Pain password
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }


    // Password
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }


    // Pole emploi ID
    public function getIdPoleEmploi()
    {
        return $this->idPoleEmploi;
    }

    public function setIdPoleEmploi($idPoleEmploi)
    {
        $this->idPoleEmploi = $idPoleEmploi;
    }


    // Type of service (ex: "Activ'Crea")
    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): self
    {
        $this->serviceType = $serviceType;

        return $this;
    }


    // Salt
    public function getSalt()
    {
        return null;
    }


    // Roles
    public function getRoles()
    {
        $roles = empty($this->roles) ? array('ROLE_USER') : $this->roles;
        return $roles;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }


    // Active/lock/expired methods
    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isOutOfDate()
    {
        return $this->registerEndDate <= (new \DateTime());
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    public function getIsArchived()
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived)
    {
        $this->isArchived = $isArchived;
    }

    // Credentials & Serialize
    public function eraseCredentials()
    {
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->password,
            $this->isActive,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            $this->isActive,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * @return Collection|WorkshopSubscribe[]
     */
    public function getSubscribes(): Collection
    {
        return $this->subscribes;
    }

    public function getSubscribeByWorkshop(Workshop $workshop)
    {
          $criteria = Criteria::create();
          $criteria->where(Criteria::expr()->eq('workshop', $workshop));

          $matches = $this->subscribes->matching($criteria);

          return (count($matches) > 0) ? $matches[0] : null;
    }

    public function addSubscribe(WorkshopSubscribe $subscribe): self
    {
        if (!$this->subscribes->contains($subscribe)) {
            $this->subscribes[] = $subscribe;
            $subscribe->setUser($this);
        }

        return $this;
    }

    public function removeSubscribe(WorkshopSubscribe $subscribe): self
    {
        if ($this->subscribes->contains($subscribe)) {
            $this->subscribes->removeElement($subscribe);
            // set the owning side to null (unless already changed)
            if ($subscribe->getUser() === $this) {
                $subscribe->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Workshop[]
     */
    public function getWorkshops(): Collection
    {
        return $this->workshops;
    }

    public function addWorkshop(Workshop $workshop): self
    {
        if (!$this->workshops->contains($workshop)) {
            $this->workshops[] = $workshop;
            $workshop->setLecturer($this);
        }

        return $this;
    }

    public function removeWorkshop(Workshop $workshop): self
    {
        if ($this->workshops->contains($workshop)) {
            $this->workshops->removeElement($workshop);
            // set the owning side to null (unless already changed)
            if ($workshop->getLecturer() === $this) {
                $workshop->setLecturer(null);
            }
        }

        return $this;
    }

    public function getHideHelpModal(): ?bool
    {
        return $this->hideHelpModal;
    }

    public function setHideHelpModal(bool $hideHelpModal): self
    {
        $this->hideHelpModal = $hideHelpModal;

        return $this;
    }

    public function getResetPassword(): ?ResetPassword
    {
        return $this->resetPassword;
    }

    public function setResetPassword(?ResetPassword $resetPassword): self
    {
        $this->resetPassword = $resetPassword;

        return $this;
    }

    public function getIsConsultant(): ?bool
    {
        return $this->isConsultant;
    }

    public function setIsConsultant(?bool $isConsultant): self
    {
        $this->isConsultant = $isConsultant;

        return $this;
    }

    public function getReferentConsultant(): ?self
    {
        return $this->referentConsultant;
    }

    public function setReferentConsultant(?self $referentConsultant): self
    {
        $this->referentConsultant = $referentConsultant;

        return $this;
    }

    public function getAskReactivationExpiresAt(): ?int
    {
        return $this->askReactivationExpiresAt;
    }

    public function setAskReactivationExpiresAt(\DateInterval $interval): self
    {
        $now = new \DateTime();
        $this->askReactivationExpiresAt = $now->add($interval)->getTimestamp();

        return $this;
    }

    /**
     * @return Collection|UserVSI[]
     */
    public function getUserVSIs(): Collection
    {
        return $this->userVSIs;
    }

    public function addUserVSI(UserVSI $userVSI): self
    {
        if (!$this->userVSIs->contains($userVSI)) {
            $this->userVSIs[] = $userVSI;
            $userVSI->setReferentConsultant($this);
        }

        return $this;
    }

    public function removeUserVSI(UserVSI $userVSI): self
    {
        if ($this->userVSIs->contains($userVSI)) {
            $this->userVSIs->removeElement($userVSI);
            // set the owning side to null (unless already changed)
            if ($userVSI->getReferentConsultant() === $this) {
                $userVSI->setReferentConsultant(null);
            }
        }

        return $this;
    }
}
