<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SurveyAnswerRepository")
 */
class SurveyAnswer
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SurveyGrade", inversedBy="surveyAnswers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyGrade;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SurveyQuestion", inversedBy="surveyAnswers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $surveyQuestion;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserVSI", inversedBy="surveyAnswers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userVSI;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurveyGrade(): ?SurveyGrade
    {
        return $this->surveyGrade;
    }

    public function setSurveyGrade(?SurveyGrade $surveyGrade): self
    {
        $this->surveyGrade = $surveyGrade;

        return $this;
    }

    public function getSurveyQuestion(): ?SurveyQuestion
    {
        return $this->surveyQuestion;
    }

    public function setSurveyQuestion(?SurveyQuestion $surveyQuestion): self
    {
        $this->surveyQuestion = $surveyQuestion;

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
}
