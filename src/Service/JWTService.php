<?php

namespace App\Service;

use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;  // Changed from Rsa\Sha256
use Lcobucci\JWT\Signer\Key\InMemory;
use DateTimeImmutable;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Psr\Clock\ClockInterface;

class JWTService
{
    private Configuration $config;
    private ClockInterface $clock;

    public function __construct(string $secretKey, ClockInterface $clock)
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey)
        );
        $this->clock = $clock;
    }

    public function createToken(string $userId, int $age, int $gender, int $loe, array $interfaces): string
    {
        try {
            $now = $this->clock->now();
            $token = $this->config->builder()
                ->issuedAt($now)
                ->canOnlyBeUsedAfter($now)
                ->expiresAt($now->modify('+24 hours'))
                ->withClaim('user_id', $userId)
                ->withClaim('age', $age)
                ->withClaim('gender', $gender)
                ->withClaim('loe', $loe)
                ->withClaim('interfaces', $interfaces)
                ->getToken($this->config->signer(), $this->config->signingKey());

            return $token->toString();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create JWT token: ' . $e->getMessage());
        }
    }

    public function getTokenData(string $jwt): ?array
    {
        try {
            $token = $this->config->parser()->parse($jwt);

            $constraints = [
                new SignedWith($this->config->signer(), $this->config->signingKey()),
                new StrictValidAt($this->clock)
            ];

            if (!$this->config->validator()->validate($token, ...$constraints)) {
                return null;
            }

            return [
                'user_id' => $token->claims()->get('user_id'),
                'age' => $token->claims()->get('age'),
                'gender' => $token->claims()->get('gender'),
                'loe' => $token->claims()->get('loe'),
                'interfaces' => $token->claims()->get('interfaces')
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}