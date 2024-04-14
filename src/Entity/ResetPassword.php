<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResetPasswordRepository")
 */
class ResetPassword
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
    private $resetToken;

    /**
     * @ORM\Column(type="integer", nullable=true) 
     */
    private $resetTokenExpiresAt;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="resetPassword", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function getResetTokenExpiresAt(): ?int
    {
        return $this->resetTokenExpiresAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Generates new reset token which expires in specified period of time.
     */
    public function generateResetToken(\DateInterval $interval): string
    {
        $now = new \DateTime();

        $this->resetToken = Uuid::uuid4()->getHex();
        $this->resetTokenExpiresAt = $now->add($interval)->getTimestamp();

        return $this->resetToken;
    }

    /**
     * Clears current reset token.
     */
    public function clearResetToken(): self
    {
        $this->resetToken          = null;
        $this->resetTokenExpiresAt = null;

        return $this;
    }

    /**
     * Checks whether specified reset token is valid.
     */
    public function isResetTokenValid(string $token): bool
    {
        return
            $this->resetToken === $token        &&
            $this->resetTokenExpiresAt !== null &&
            $this->resetTokenExpiresAt > time();
    }
}
