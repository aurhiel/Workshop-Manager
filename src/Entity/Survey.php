<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SurveyRepository")
 */
class Survey
{
    public const DEFAULT_SURVEY_SLUG = 'vsi-1';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=127)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=127)
     */
    private $slug;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     */
    private $isDefault = false;

    /**
     * @ORM\Column(name="enable_workshops_grade", type="boolean")
     */
    private $enableWorkshopsGrade = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyStep", mappedBy="survey", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveySteps;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyGrade", mappedBy="survey", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyGrades;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyToken", mappedBy="survey")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyTokens;

    public function __construct()
    {
        $this->surveySteps = new ArrayCollection();
        $this->surveyGrades = new ArrayCollection();
        $this->surveyTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getEnableWorkshopsGrade(): ?bool
    {
        return $this->enableWorkshopsGrade;
    }

    public function setEnableWorkshopsGrade(bool $enableWorkshopsGrade): self
    {
        $this->enableWorkshopsGrade = $enableWorkshopsGrade;

        return $this;
    }

    /**
     * @return Collection|SurveyStep[]
     */
    public function getSurveySteps(): Collection
    {
        return $this->surveySteps;
    }

    public function getStepFromPosition($step_position)
    {
        $current_step = null;
        if (count($this->surveySteps) > 0) {
            foreach ($this->surveySteps as $step) {
                if ($step->getPosition() === $step_position) {
                    $current_step = $step;
                    break;
                }
            }
        }

        return $current_step;
    }

    public function addSurveyStep(SurveyStep $surveyStep): self
    {
        if (!$this->surveySteps->contains($surveyStep)) {
            $this->surveySteps[] = $surveyStep;
            $surveyStep->setSurvey($this);
        }

        return $this;
    }

    public function removeSurveyStep(SurveyStep $surveyStep): self
    {
        if ($this->surveySteps->contains($surveyStep)) {
            $this->surveySteps->removeElement($surveyStep);
            // set the owning side to null (unless already changed)
            if ($surveyStep->getSurvey() === $this) {
                $surveyStep->setSurvey(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SurveyGrade[]
     */
    public function getSurveyGrades(): Collection
    {
        return $this->surveyGrades;
    }

    public function addSurveyGrade(SurveyGrade $surveyGrade): self
    {
        if (!$this->surveyGrades->contains($surveyGrade)) {
            $this->surveyGrades[] = $surveyGrade;
            $surveyGrade->setSurvey($this);
        }

        return $this;
    }

    public function removeSurveyGrade(SurveyGrade $surveyGrade): self
    {
        if ($this->surveyGrades->contains($surveyGrade)) {
            $this->surveyGrades->removeElement($surveyGrade);
            // set the owning side to null (unless already changed)
            if ($surveyGrade->getSurvey() === $this) {
                $surveyGrade->setSurvey(null);
            }
        }

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
            $surveyToken->setSurvey($this);
        }

        return $this;
    }

    public function removeSurveyToken(SurveyToken $surveyToken): self
    {
        if ($this->surveyTokens->contains($surveyToken)) {
            $this->surveyTokens->removeElement($surveyToken);
            // set the owning side to null (unless already changed)
            if ($surveyToken->getSurvey() === $this) {
                $surveyToken->setSurvey(null);
            }
        }

        return $this;
    }

    public function getSurveyQuestionsCount(): int
    {
        $steps    = $this->getSurveySteps();
        $counter  = 0;
        foreach ($steps as $step) {
            $counter += count($step->getSurveyQuestions());
        }

        return $counter;
    }
}
