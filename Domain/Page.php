<?php


namespace Domain;


class Page
{
    /**
     * Page URL
     *
     * @var string
     */
    protected $url;

    /**
     * Children pages
     *
     * @var array
     */
    protected $children = [];

    /**
     * <img> tags on page counter.
     *
     * @var int|null
     */
    protected $imgCount;

    /**
     * Page processing time, seconds
     *
     * @var float|null $processingTime
     */
    protected $processingTime;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return bool
     */
    public function isNotProcessed(): bool
    {
        return $this->processingTime === null;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return self
     */
    public function setChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImgCount()
    {
        return $this->imgCount;
    }

    /**
     * @param int $imgCount
     *
     * @return self
     */
    public function setImgCount(int $imgCount): self
    {
        $this->imgCount = $imgCount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getProcessingTime(): ?float
    {
        return $this->processingTime;
    }

    /**
     * @param float $processingTime
     *
     * @return self
     */
    public function setProcessingTime(float $processingTime): self
    {
        $this->processingTime = $processingTime;
        return $this;
    }
}