<?php

namespace App\Entity;

use App\Entity\Sensor;
use App\Entity\Wine;
use App\Repository\MeasurementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeasurementRepository::class)]
class Measurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $sensor_id = null;

    #[ORM\Column]
    private ?int $wine_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(nullable: true)]
    private ?float $temperature = null;

    #[ORM\Column(nullable: true)]
    private ?float $alcohol_content = null;

    #[ORM\Column(nullable: true)]
    private ?float $ph = null;

    #[ORM\ManyToOne(targetEntity: Sensor::class)]
    private ?Sensor $sensor = null;

    #[ORM\ManyToOne(targetEntity: Wine::class, cascade: ['persist', 'remove'])]
    private ?Wine $wine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getSensorId(): ?int
    {
        return $this->sensor_id;
    }

    public function setSensorId(int $sensor_id): static
    {
        $this->sensor_id = $sensor_id;

        return $this;
    }

    public function getWineId(): ?int
    {
        return $this->wine_id;
    }

    public function setWineId(int $wine_id): static
    {
        $this->wine_id = $wine_id;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getAlcoholContent(): ?float
    {
        return $this->alcohol_content;
    }

    public function setAlcoholContent(?float $alcohol_content): static
    {
        $this->alcohol_content = $alcohol_content;

        return $this;
    }

    public function getPh(): ?float
    {
        return $this->ph;
    }

    public function setPh(?float $ph): static
    {
        $this->ph = $ph;

        return $this;
    }

    public function setSensor(Sensor $sensor): static
    {
        $this->sensor = $sensor;

        return $this;
    }

    public function setWine(Wine $wine): static
    {
        $this->wine = $wine;

        return $this;
    }

    public function getWine(): ?Wine
    {
        return $this->wine;
    }

    public function getSensor(): ?Sensor
    {
        return $this->sensor;
    }

}
