<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Repository\UserDataRepository;
use ArrayObject;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: UserDataRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/api/createentry',
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'email' => [
                                        'type' => 'string',
                                        'format' => 'email',
                                        'example'=> 'johndoe@example.com',
                                    ],
                                    'age' => [
                                        'type' => 'integer',
                                        'example'=> 21,
                                    ],
                                    'gender' => [
                                        'type' => 'integer',
                                        'example'=> 2,
                                    ],
                                    'loe' => [
                                        'type' => 'integer',
                                        'example'=> 3,
                                    ],
                                ],
                                'required' => ['email','age','gender','loe'],
                            ]
                        ]
                    ])
                )
            ),
            description: 'Creates a new entry',
            name: 'create_entry'
        ),
         new Post(
             uriTemplate: '/api/sendevaluation',
             openapi: new Model\Operation(
                 requestBody: new Model\RequestBody(
                     content: new ArrayObject([
                         'application/json' => [
                             'schema' => [
                                 'type' => 'object',
                                 'properties' => [
                                     'userId' => [
                                         'type' => 'string',
                                         'example'=> 'hash21623123m',
                                     ],
                                     'interfaceName' => [
                                         'type' => 'string',
                                         'example'=> 'LUI3'
                                     ],
                                     'grade1' => [
                                         'type' => 'integer',
                                         'example'=> 1
                                     ],
                                     'grade2' => [
                                         'type' => 'integer',
                                         'example'=> 1
                                     ],
                                     'grade3' => [
                                         'type' => 'integer',
                                         'example'=> 1
                                     ],
                                 ],
                                 'required' => ['email','age','gender','loe'],
                             ]
                         ]
                     ])
                 )
             ),
             description: 'send user evaluation of a test',
             name: 'send_evaluation'
         )
    ],
    formats: ['json']
)]
class UserData
{
    #[ORM\Id]
    #[ORM\Column(length: 64, unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt;

    //płeć
    // 0 - wolę nie podawać
    // 1 - kobieta
    // 2 - mężczyzna
    // 3 - inne
    #[ORM\Column(type: 'integer')]
    private int $gender = 0;

    // wolę nie podawać
    #[ORM\Column(type: 'integer')]
    private int $age = 0;

    //wykształcenie - level of education
    // 0 - wolę nie podawać
    // 1 - podstawowe
    // 2 - zasadnicze zawodowe
    // 3 - średnie
    // 4 - wyższe
    #[ORM\Column(type: 'integer')]
    private int $loe = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setIdFromEmail(string $email): self
    {
        $email = strtolower($email);
        $this->id = hash('sha256', $email);
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getGender(): int
    {
        return $this->gender;
    }

    public function setGender(int $gender): void
    {
        $this->gender = $gender;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getLoe(): int
    {
        return $this->loe;
    }

    public function setLoe(int $loe): void
    {
        $this->loe = $loe;
    }


}