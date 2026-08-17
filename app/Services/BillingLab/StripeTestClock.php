<?php

namespace App\Services\BillingLab;

use RuntimeException;
use Stripe\StripeClient;

class StripeTestClock
{
    public function client(): StripeClient
    {
        BillingLabGuard::assertCanCallStripe();

        return new StripeClient(BillingLabGuard::secret());
    }

    /**
     * @return array{id: string, frozen_time: int}
     */
    public function create(string $name, ?int $frozenTime = null): array
    {
        $frozenTime ??= time();
        $clock = $this->client()->testHelpers->testClocks->create([
            'frozen_time' => $frozenTime,
            'name' => $name,
        ]);

        return [
            'id' => (string) $clock->id,
            'frozen_time' => (int) $clock->frozen_time,
        ];
    }

    public function advance(string $clockId, int $frozenTime, int $timeoutSeconds = 60): void
    {
        $client = $this->client();
        $client->testHelpers->testClocks->advance($clockId, [
            'frozen_time' => $frozenTime,
        ]);

        $deadline = time() + $timeoutSeconds;
        do {
            $clock = $client->testHelpers->testClocks->retrieve($clockId);
            $status = (string) ($clock->status ?? '');
            if ($status === 'ready') {
                return;
            }
            if ($status === 'internal_failure') {
                throw new RuntimeException('Stripe Test Clock a échoué (internal_failure).');
            }
            usleep(500_000);
        } while (time() < $deadline);

        throw new RuntimeException('Stripe Test Clock n’est pas passé à ready à temps.');
    }

    public function delete(string $clockId): void
    {
        try {
            $this->client()->testHelpers->testClocks->delete($clockId);
        } catch (\Throwable) {
            // Horloge déjà supprimée ou expirée.
        }
    }
}
