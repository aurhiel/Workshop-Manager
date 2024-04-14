<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 */
class Address
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
    private $name;

    /**
     * @ORM\Column(type="float")
     */
    private $lat_position;

    /**
     * @ORM\Column(type="float")
     */
    private $lng_position;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Workshop", mappedBy="address")
     */
    private $workshops;

    public function __construct()
    {
        $this->workshops = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLatPosition(): ?float
    {
        return $this->lat_position;
    }

    public function setLatPosition(float $lat_position): self
    {
        $this->lat_position = $lat_position;

        return $this;
    }

    public function getLngPosition(): ?float
    {
        return $this->lng_position;
    }

    public function setLngPosition(float $lng_position): self
    {
        $this->lng_position = $lng_position;

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
            $workshop->setAddress($this);
        }

        return $this;
    }

    public function removeWorkshop(Workshop $workshop): self
    {
        if ($this->workshops->contains($workshop)) {
            $this->workshops->removeElement($workshop);
            // set the owning side to null (unless already changed)
            if ($workshop->getAddress() === $this) {
                $workshop->setAddress(null);
            }
        }

        return $this;
    }
}
