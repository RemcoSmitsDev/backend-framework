<?php

namespace Framework\Content;

final class Seo
{
    /**
     * @var string Keeps track of description
     */
    public string $description = '';

    /**
     * @var string Keeps track of image url
     */
    public string $imageURL = '';

    /**
     * Set the description for seo tags.
     *
     * @param string $description
     *
     * @return self
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set image url for seo tags.
     *
     * @param string $imageURL
     *
     * @return self
     */
    public function image(string $imageURL): self
    {
        $this->imageURL = $imageURL;

        return $this;
    }

    /**
     * Returns description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Return image url.
     *
     * @return string
     */
    public function getImageURL(): string
    {
        return $this->imageURL;
    }
}
