<?php
/**
 * CustomerRepositoryInterface.php
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
declare(strict_types=1);

namespace FireflyIII\Repositories\Customer;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Customer;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface CustomerRepositoryInterface.
 */
interface CustomerRepositoryInterface
{

    /**
     * @param Customer $customer
     *
     * @return bool
     */
    public function destroy(Customer $customer): bool;

    /**
     * Delete all customers.
     */
    public function destroyAll(): void;

    /**
     * Find a customer or return NULL
     *
     * @param int $customerId
     *
     * @return Customer|null
     */
    public function find(int $customerId): ?Customer;

    /**
     * Find a customer.
     *
     * @param string $name
     *
     * @return Customer|null
     */
    public function findByName(string $name): ?Customer;

    /**
     * @param int|null    $customerId
     * @param string|null $customerName
     *
     * @return Customer|null
     */
    public function findCustomer(?int $customerId, ?string $customerName): ?Customer;

    /**
     * @param Customer $customer
     *
     * @return Carbon|null
     */
    public function firstUseDate(Customer $customer): ?Carbon;

    /**
     * @param Customer $customer
     *
     * @return Collection
     */
    public function getAttachments(Customer $customer): Collection;

    /**
     * Get all customers with ID's.
     *
     * @param array $customerIds
     *
     * @return Collection
     */
    public function getByIds(array $customerIds): Collection;

    /**
     * Returns a list of all the customers belonging to a user.
     *
     * @return Collection
     */
    public function getCustomers(): Collection;

    /**
     * @param Customer $customer
     *
     * @return string|null
     */
    public function getNoteText(Customer $customer): ?string;

    /**
     * Return most recent transaction(journal) date or null when never used before.
     *
     * @param Customer   $customer
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    public function lastUseDate(Customer $customer, Collection $accounts): ?Carbon;

    /**
     * Remove notes.
     *
     * @param Customer $customer
     */
    public function removeNotes(Customer $customer): void;

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchCustomer(string $query, int $limit): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return Customer
     * @throws FireflyException
     */
    public function store(array $data): Customer;

    /**
     * @param Customer $customer
     * @param array    $data
     *
     * @return Customer
     */
    public function update(Customer $customer, array $data): Customer;

    /**
     * @param Customer $customer
     * @param string   $notes
     */
    public function updateNotes(Customer $customer, string $notes): void;
}
