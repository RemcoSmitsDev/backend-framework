<?php

namespace Framework\Content;

class Seo
{

  public static string $description = '';
  public static string $imageURL = '';

  public static function setDescription(string $description): self
  {
    self::$description = $description;
    return new self;
  }

  public static function setImageURL(string $imageURL): self
  {
    self::$imageURL = $imageURL;
    return new self;
  }

  public static function getDescription(): string
  {
    return self::$description;
  }

  public static function getImageURL(): string
  {
    return self::$imageURL;
  }
}
