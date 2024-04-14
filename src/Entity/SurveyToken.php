<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SurveyTokenRepository")
 */
class SurveyToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $expiresAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserVSI", inversedBy="surveyTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userVSI;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Survey", inversedBy="surveyTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $survey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
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

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * Generates new token which expires in specified period of time.
     */
    public function generateToken(\DateInterval $interval): string
    {
        $now = new \DateTime();

        $this->token      = Uuid::uuid4()->getHex();
        $this->expiresAt  = $now->add($interval);

        // Force expire date at the end of the day & then convert to timestamp
        $this->expiresAt->setTime(23, 59, 59);
        $this->expiresAt = $this->expiresAt->getTimestamp();

        return $this->token;
    }

    /**
     * Clears current token.
     */
    public function clearToken(): self
    {
        $this->token      = null;
        $this->expiresAt  = null;

        return $this;
    }

    /**
     * Reset expires at, in order to let users re-access their survey answers later
     */
    public function resetExpiresAt(\DateInterval $interval): self
    {
        $now = new \DateTime();
        $this->expiresAt = $now->add($interval);

        // Force expire date at the end of the day & then convert to timestamp
        $this->expiresAt->setTime(23, 59, 59);
        $this->expiresAt = $this->expiresAt->getTimestamp();

        return $this;
    }

    /**
     * Checks whether specified reset token is valid.
     */
    public function isTokenValid(string $token): bool
    {
        return
            $this->token === $token   &&
            $this->expiresAt !== null &&
            $this->expiresAt > time();
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt <= time();
    }
}
