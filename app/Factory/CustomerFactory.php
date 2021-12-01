<?php
/**
 * CategoryFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** @noinspection MultipleReturnStatementsInspection */
declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Customer;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class CustomerFactory
 */
class CustomerFactory
{
    private User $user;

    /**
     * @param int|null    $customerId
     * @param null|string $customerName
     *
     * @return Customer|null
     * @throws FireflyException
     */
    public function findOrCreate(?int $customerId, ?string $customerName): ?Customer
    {
        $customerId   = (int)$customerId;
        $customerName = (string)$customerName;

        Log::debug(sprintf('Going to find customer with ID %d and name "%s"', $customerId, $customerName));

        if ('' === $customerName && 0 === $customerId) {
            return null;
        }
        // first by ID:
        if ($customerId > 0) {
            /** @var Customer $customer */
            $customer = $this->user->customers()->find($customerId);
            if (null !== $customer) {
                return $customer;
            }
        }

        if ('' !== $customerName) {
            $customer = $this->findByName($customerName);
            if (null !== $customer) {
                return $customer;
            }

            try {
                return Customer::create(
                    [
                        'user_id' => $this->user->id,
                        'name'    => $customerName,
                    ]
                );
            } catch (QueryException $e) {
                Log::error($e->getMessage());
                throw new FireflyException('400003: Could not store new customer.', 0, $e);
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return Customer|null
     */
    public function findByName(string $name): ?Customer
    {
        return $this->user->customers()->where('name', $name)->first();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
