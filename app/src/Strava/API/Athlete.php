<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class Athlete implements \Stringable
{
    public function __construct(
        public int $id,
        public string $username,
        public string $firstname,
        public string $lastname,
        public Gender $sex,
        public bool $premium,
        public string $profileMedium,
        public string $profile,
    ) {
    }

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
    public static function fromData(array $data): self
    {
        return new self(
            $data['id'],
            $data['username'],
            $data['firstname'],
            $data['lastname'],
            Gender::from($data['sex']),
            $data['premium'],
            $data['profile_medium'],
            $data['profile'],
        );
    }

    /**
     * @return array{
     *  id: int,
     *  username: string,
     *  firstname: string,
     *  lastname: string,
     *  sex: string,
     *  premium: bool,
     *  profile_medium: string,
     *  profile: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'sex' => $this->sex->value,
            'premium' => $this->premium,
            'profile_medium' => $this->profileMedium,
            'profile' => $this->profile,
        ];
    }

    public function getName(): string
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function __toString(): string
    {
        return sprintf('%s (Strava-ID: %d)', $this->getName(), $this->id);
    }
}
