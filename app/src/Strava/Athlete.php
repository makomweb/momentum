<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class Athlete
{
    /**
     * @param array{
     *  id: int,
     *  username: string,
     *  firstname: string,
     *  lastname: string,
     *  sex: string,
     *  premium: bool,
     *  profile_medium: string,
     *  profile: string
     * } $data
     */
    public function __construct(private array $data)
    {
    }

    public function getId(): int
    {
        return $this->data['id'];
    }

    public function getUsername(): string
    {
        return $this->data['username'];
    }

    public function getName(): string
    {
        return $this->data['firstname'].' '.$this->data['lastname'];
    }

    public function getGender(): Gender
    {
        return Gender::tryFrom($this->data['sex']) ?? Gender::DIVERSE;
    }

    public function getProfile(): string
    {
        return $this->data['profile'];
    }

    public function getProfileMedium(): string
    {
        return $this->data['profile_medium'];
    }
}
