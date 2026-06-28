<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'content')]
class Content
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $type = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url = null;

    #[ORM\Column(name: 'release_date', type: 'text', nullable: true)]
    private ?string $releaseDate = null;

    #[ORM\Column(type: 'text')]
    private ?string $status = null;

    #[ORM\Column(name: 'cover_file_id', type: 'text', nullable: true)]
    private ?string $coverFileId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'user_id', nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'created_at', type: 'text', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?string $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'text', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?string $updatedAt = null;

    #[ORM\Column(name: 'likes_count', type: 'integer', options: ['default' => 0])]
    private int $likesCount = 0;

    #[ORM\Column(name: 'dislikes_count', type: 'integer', options: ['default' => 0])]
    private int $dislikesCount = 0;

    #[ORM\Column(name: 'views_count', type: 'integer', options: ['default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'trending_score', type: 'float', options: ['default' => 0.0])]
    private float $trendingScore = 0.0;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'content_categories')]
    #[ORM\JoinColumn(name: 'content_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private Collection $categories;

        #[ORM\OneToMany(mappedBy: "content", targetEntity: ContentStaff::class)]
    private Collection $staff;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->staff = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getReleaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?string $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCoverFileId(): ?string
    {
        return $this->coverFileId;
    }

    public function setCoverFileId(?string $coverFileId): self
    {
        $this->coverFileId = $coverFileId;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): self
    {
        $this->likesCount = $likesCount;

        return $this;
    }

    public function getDislikesCount(): int
    {
        return $this->dislikesCount;
    }

    public function setDislikesCount(int $dislikesCount): self
    {
        $this->dislikesCount = $dislikesCount;

        return $this;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function setViewsCount(int $viewsCount): self
    {
        $this->viewsCount = $viewsCount;

        return $this;
    }

    public function getTrendingScore(): float
    {
        return $this->trendingScore;
    }

    public function setTrendingScore(float $trendingScore): self
    {
        $this->trendingScore = $trendingScore;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getStaff(): Collection
    {
        return $this->staff;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }
}
