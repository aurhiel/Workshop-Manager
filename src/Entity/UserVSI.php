<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserVSIRepository")
 */
class UserVSI
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=190)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=127, nullable=true)
     */
    private $idVSI;

    /**
     * @ORM\Column(type="string", length=127)
     */
    private $idCohort;

    /**
     * @ORM\Column(type="date")
     */
    private $workshopEndDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="userVSIs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $referentConsultant;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyToken", mappedBy="userVSI", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     */
    private $surveyTokens;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyAnswer", mappedBy="userVSI", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     */
    private $surveyAnswers;

    /*
    */
    private $surveyAnswersCount;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\WorkshopSubscribe", mappedBy="userVSI", orphanRemoval=true)
     */
    private $subscribes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Workshop", mappedBy="lecturer")
     */
    private $workshops;

    public function __construct()
    {
        $this->surveyTokens = new ArrayCollection();
        $this->surveyAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getMailingFormat()
    {
        return array($this->getEmail() => $this->getFirstname().' '.$this->getLastname());
    }

    public function getIdVSI()
    {
        return $this->idVSI;
    }

    public function setIdVSI($idVSI): self
    {
        $this->idVSI = $idVSI;

        return $this;
    }

    public function getIdCohort(): ?string
    {
        return $this->idCohort;
    }

    public function setIdCohort(string $idCohort): self
    {
        $this->idCohort = $idCohort;

        return $this;
    }

    public function getWorkshopEndDate(): ?\DateTimeInterface
    {
        return $this->workshopEndDate;
    }

    public function setWorkshopEndDate(\DateTimeInterface $workshopEndDate): self
    {
        $this->workshopEndDate = $workshopEndDate;

        return $this;
    }

    public function getReferentConsultant(): ?User
    {
        return $this->referentConsultant;
    }

    public function setReferentConsultant(?User $referentConsultant): self
    {
        $this->referentConsultant = $referentConsultant;

        return $this;
    }

    /**
     * @return Collection|SurveyToken[]
     */
    public function getSurveyTokens(): Collection
    {
        return $this->surveyTokens;
    }

    public function addSurveyToken(SurveyToken $surveyToken): self
    {
        if (!$this->surveyTokens->contains($surveyToken)) {
            $this->surveyTokens[] = $surveyToken;
            $surveyToken->setUserVSI($this);
        }

        return $this;
    }

    public function removeSurveyToken(SurveyToken $surveyToken): self
    {
        if ($this->surveyTokens->contains($surveyToken)) {
            $this->surveyTokens->removeElement($surveyToken);
            // set the owning side to null (unless already changed)
            if ($surveyToken->getUserVSI() === $this) {
                $surveyToken->setUserVSI(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SurveyAnswer[]
     */
    public function getSurveyAnswers(): Collection
    {
        return $this->surveyAnswers;
    }

    public function addSurveyAnswer(SurveyAnswer $surveyAnswer): self
    {
        if (!$this->surveyAnswers->contains($surveyAnswer)) {
            $this->surveyAnswers[] = $surveyAnswer;
            $surveyAnswer->setUserVSI($this);
        }

        return $this;
    }

    public function removeSurveyAnswer(SurveyAnswer $surveyAnswer): self
    {
        if ($this->surveyAnswers->contains($surveyAnswer)) {
            $this->surveyAnswers->removeElement($surveyAnswer);
            // set the owning side to null (unless already changed)
            if ($surveyAnswer->getUserVSI() === $this) {
                $surveyAnswer->setUserVSI(null);
            }
        }

        return $this;
    }

    public function setSurveyAnswersCount($nbAnswers)
    {
        $this->surveyAnswersCount = intval($nbAnswers);
    }

    public function getSurveyAnswersCount()
    {
        return $this->surveyAnswersCount;
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
            $subscribe->setUserVSI($this);
        }

        return $this;
    }

    public function removeSubscribe(WorkshopSubscribe $subscribe): self
    {
        if ($this->subscribes->contains($subscribe)) {
            $this->subscribes->removeElement($subscribe);
            // set the owning side to null (unless already changed)
            if ($subscribe->getUserVSI() === $this) {
                $subscribe->setUserVSI(null);
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
}
