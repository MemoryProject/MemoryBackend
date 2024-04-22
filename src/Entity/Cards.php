<?php

namespace App\Entity;

use App\Repository\CardsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardsRepository::class)]
class Cards
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(max: 255, maxMessage: "L'url de l'image ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $image_url = null;

    #[ORM\ManyToOne]
    private ?Themes $theme_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    public function getThemeId(): ?Themes
    {
        return $this->theme_id;
    }

    public function setThemeId(?Themes $theme_id): static
    {
        $this->theme_id = $theme_id;

        return $this;
    }
}
