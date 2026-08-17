<?php

namespace App\Services\BillingLab;

class ScenarioResult
{
    public function __construct(
        public bool $ok,
        public string $status,
        public string $message,
        public array $details = [],
        public array $findings = [],
    ) {}

    public static function pass(string $message, array $details = [], array $findings = []): self
    {
        return new self(true, 'pass', $message, $details, $findings);
    }

    public static function fail(string $message, array $details = [], array $findings = []): self
    {
        return new self(false, 'fail', $message, $details, $findings);
    }

    public static function evidenceRisk(string $message, array $details = [], array $findings = []): self
    {
        return new self(true, 'evidence_risk', $message, $details, $findings);
    }

    public static function evidenceSafe(string $message, array $details = [], array $findings = []): self
    {
        return new self(true, 'evidence_safe', $message, $details, $findings);
    }

    public static function skipped(string $message, array $details = []): self
    {
        return new self(true, 'skipped', $message, $details);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'status' => $this->status,
            'message' => $this->message,
            'details' => $this->details,
            'findings' => $this->findings,
        ];
    }
}
