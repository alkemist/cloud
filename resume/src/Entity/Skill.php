<?php

namespace App\Entity;

use App\Helper\StringHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SkillRepository")
 * @UniqueEntity("slug")
 */
class Skill
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    const TYPE_SOFTWARE    = "software";
    const TYPE_FRAMEWORK = "framework";
    const TYPE_PLATFORM = "platform";
    const TYPE_LANGUAGE  = "language";
    const TYPE_OS  = "os";
    const TYPE_VERSION  = "version";

    /** @var array user friendly named type */
    const TYPES = [
        'Software' => self::TYPE_SOFTWARE,
        'Framework' => self::TYPE_FRAMEWORK,
        'Platform' => self::TYPE_PLATFORM,
        'Language' => self::TYPE_LANGUAGE,
        'OS' => self::TYPE_OS,
        'Version' => self::TYPE_VERSION,
    ];

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        $typeName = array_flip(self::TYPES);
        if (!isset($typeName[$this->type])) {
            return null;
        }

        return $typeName[$this->type];
    }

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    private $level;

    /**
     * @var bool
     *
     * @ORM\Column(name="on_homepage", type="boolean", nullable=false)
     */
    private $onHomepage;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Experience", mappedBy="skills", cascade={"persist"})
     */
    private $experiences;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Skill", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Skill", mappedBy="parent")
     */
    private $children;

    public function __construct()
    {
        $this->experiences = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->level = 0;
    }

    public function getId(): ?int
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
        $this->setSlug(StringHelper::slugify($name));

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getOnHomepage(): ?bool
    {
        return $this->onHomepage;
    }

    public function setOnHomepage(bool $onHomepage): self
    {
        $this->onHomepage = $onHomepage;

        return $this;
    }

    /**
     * @return Collection|Experience[]
     */
    public function getExperiences(): Collection
    {
        return $this->experiences;
    }

    public function addExperience(Experience $experience): self
    {
        if (!$this->experiences->contains($experience)) {
            $this->experiences[] = $experience;
            $experience->addSkill($this);
        }

        return $this;
    }

    public function removeExperience(Experience $experience): self
    {
        if ($this->experiences->contains($experience)) {
            $this->experiences->removeElement($experience);
            $experience->removeSkill($this);
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getParentName(): ?string
    {
        return $this->parent ? $this->parent->getName() : null;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|Skill[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Skill $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Skill $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @param Skill[] $parents
     * @return Skill[]
     */
    public function getAllParents(array $parents = []): array
    {
        if ($this->getParent()) {
            $parent = $this->getParent();

            if (!in_array($parent, $parents)) {
                $parents[] = $parent;

                return $parent->getAllParents($parents);
            }
        }

        return $parents;
    }
}
