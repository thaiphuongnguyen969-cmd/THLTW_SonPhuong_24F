<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddressService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes): Address {
            $lockedUser = $this->lockUser($user);
            $hasAddresses = $lockedUser->addresses()->lockForUpdate()->exists();

            return $lockedUser->addresses()->create([
                ...$attributes,
                'is_default' => ! $hasAddresses,
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, int $addressId, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $addressId, $attributes): Address {
            $lockedUser = $this->lockUser($user);
            $address = $lockedUser->addresses()
                ->whereKey($addressId)
                ->lockForUpdate()
                ->firstOrFail();
            $address->update($attributes);

            return $address->refresh();
        }, 3);
    }

    public function setDefault(User $user, int $addressId): Address
    {
        return DB::transaction(function () use ($user, $addressId): Address {
            $lockedUser = $this->lockUser($user);
            $addresses = $lockedUser->addresses()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $address = $addresses->firstWhere('id', $addressId);

            abort_unless($address, 404);

            $lockedUser->addresses()
                ->where('id', '<>', $address->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
            $address->forceFill(['is_default' => true])->save();

            return $address->refresh();
        }, 3);
    }

    public function delete(User $user, int $addressId): void
    {
        DB::transaction(function () use ($user, $addressId): void {
            $lockedUser = $this->lockUser($user);
            $addresses = $lockedUser->addresses()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $address = $addresses->firstWhere('id', $addressId);

            abort_unless($address, 404);

            $replacement = $address->is_default
                ? $this->replacementAddress($addresses, $address)
                : null;

            $address->delete();

            if ($replacement) {
                $lockedUser->addresses()
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
                $replacement->forceFill(['is_default' => true])->save();
            }
        }, 3);
    }

    private function lockUser(User $user): User
    {
        return User::query()
            ->whereKey($user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, Address>  $addresses
     */
    private function replacementAddress(Collection $addresses, Address $deleted): ?Address
    {
        $remaining = $addresses
            ->reject(fn (Address $address): bool => $address->is($deleted))
            ->sortBy('id')
            ->values();

        return $remaining->first(fn (Address $address): bool => $address->id > $deleted->id)
            ?? $remaining->last();
    }
}
