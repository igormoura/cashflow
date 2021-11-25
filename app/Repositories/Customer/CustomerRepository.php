<?php
/**
 * CustomerRepository.php
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
use DB;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\CustomerFactory;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Customer;
use FireflyIII\Models\Note;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\RuleAction;
use FireflyIII\Services\Internal\Destroy\CustomerDestroyService;
use FireflyIII\Services\Internal\Update\CustomerUpdateService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class CustomerRepository.
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    private User $user;

    /**
     * @param Customer $customer
     *
     * @return bool
     *

     */
    public function destroy(Customer $customer): bool
    {
        /** @var CustomerDestroyService $service */
        $service = app(CustomerDestroyService::class);
        $service->destroy($customer);

        return true;
    }

    /**
     * Delete all customers.
     */
    public function destroyAll(): void
    {
        $customers = $this->getCustomers();
        /** @var Customer $customer */
        foreach ($customers as $customer) {
            DB::table('customer_transaction')->where('customer_id', $customer->id)->delete();
            DB::table('customer_transaction_journal')->where('customer_id', $customer->id)->delete();
            RecurrenceTransactionMeta::where('name', 'customer_id')->where('value', $customer->id)->delete();
            RuleAction::where('action_type', 'set_customer')->where('action_value', $customer->name)->delete();
            $customer->delete();
        }
    }

    /**
     * Find a customer or return NULL
     *
     * @param int $customerId
     *
     * @return Customer|null
     */
    public function find(int $customerId): ?Customer
    {
        return $this->user->customers()->find($customerId);
    }

    /**
     * Find a customer.
     *
     * @param string $name
     *
     * @return Customer|null
     */
    public function findByName(string $name): ?Customer
    {
        return $this->user->customers()->where('name', $name)->first(['customers.*']);
    }

    /**
     * @param int|null    $customerId
     * @param string|null $customerName
     *
     * @return Customer|null
     * @throws FireflyException
     */
    public function findCustomer(?int $customerId, ?string $customerName): ?Customer
    {
        Log::debug('Now in findCustomer()');
        Log::debug(sprintf('Searching for customer with ID #%d...', $customerId));
        $result = $this->find((int)$customerId);
        if (null === $result) {
            Log::debug(sprintf('Searching for customer with name %s...', $customerName));
            $result = $this->findByName((string)$customerName);
            if (null === $result && '' !== (string)$customerName) {
                // create it!
                $result = $this->store(['name' => $customerName]);
            }
        }
        if (null !== $result) {
            Log::debug(sprintf('Found customer #%d: %s', $result->id, $result->name));
        }
        Log::debug(sprintf('Found customer result is null? %s', var_export(null === $result, true)));

        return $result;
    }

    /**
     * @param Customer $customer
     *
     * @return Carbon|null
     *
     */
    public function firstUseDate(Customer $customer): ?Carbon
    {
        $firstJournalDate     = $this->getFirstJournalDate($customer);
        $firstTransactionDate = $this->getFirstTransactionDate($customer);

        if (null === $firstTransactionDate && null === $firstJournalDate) {
            return null;
        }
        if (null === $firstTransactionDate) {
            return $firstJournalDate;
        }
        if (null === $firstJournalDate) {
            return $firstTransactionDate;
        }

        if ($firstTransactionDate < $firstJournalDate) {
            return $firstTransactionDate;
        }

        return $firstJournalDate;
    }

    /**
     * @inheritDoc
     */
    public function getAttachments(Customer $customer): Collection
    {
        $set = $customer->attachments()->get();

        /** @var Storage $disk */
        $disk = Storage::disk('upload');

        return $set->each(
            static function (Attachment $attachment) use ($disk) {
                $notes                   = $attachment->notes()->first();
                $attachment->file_exists = $disk->exists($attachment->fileName());
                $attachment->notes       = $notes ? $notes->text : '';

                return $attachment;
            }
        );
    }

    /**
     * Get all customers with ID's.
     *
     * @param array $customerIds
     *
     * @return Collection
     */
    public function getByIds(array $customerIds): Collection
    {
        return $this->user->customers()->whereIn('id', $customerIds)->get();
    }

    /**
     * Returns a list of all the customers belonging to a user.
     *
     * @return Collection
     */
    public function getCustomers(): Collection
    {
        return $this->user->customers()->with(['attachments'])->orderBy('name', 'ASC')->get();
    }

    /**
     * @inheritDoc
     */
    public function getNoteText(Customer $customer): ?string
    {
        $dbNote = $customer->notes()->first();
        if (null === $dbNote) {
            return null;
        }

        return $dbNote->text;
    }

    /**
     * @param Customer   $customer
     * @param Collection $accounts
     *
     * @return Carbon|null
     * @throws Exception
     */
    public function lastUseDate(Customer $customer, Collection $accounts): ?Carbon
    {
        $lastJournalDate     = $this->getLastJournalDate($customer, $accounts);
        $lastTransactionDate = $this->getLastTransactionDate($customer, $accounts);

        if (null === $lastTransactionDate && null === $lastJournalDate) {
            return null;
        }
        if (null === $lastTransactionDate) {
            return $lastJournalDate;
        }
        if (null === $lastJournalDate) {
            return $lastTransactionDate;
        }

        if ($lastTransactionDate > $lastJournalDate) {
            return $lastTransactionDate;
        }

        return $lastJournalDate;
    }

    /**
     * @param Customer $customer
     */
    public function removeNotes(Customer $customer): void
    {
        $customer->notes()->delete();
    }

    /**
     * @param string $query
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchCustomer(string $query, int $limit): Collection
    {
        $search = $this->user->customers();
        if ('' !== $query) {
            $search->where('name', 'LIKE', sprintf('%%%s%%', $query));
        }

        return $search->take($limit)->get();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Customer
     * @throws FireflyException
     */
    public function store(array $data): Customer
    {
        /** @var CustomerFactory $factory */
        $factory = app(CustomerFactory::class);
        $factory->setUser($this->user);

        $customer = $factory->findOrCreate(null, $data['name']);

        if (null === $customer) {
            throw new FireflyException(sprintf('400003: Could not store new customer with name "%s"', $data['name']));
        }

        if (array_key_exists('notes', $data) && '' === $data['notes']) {
            $this->removeNotes($customer);
        }
        if (array_key_exists('notes', $data) && '' !== $data['notes']) {
            $this->updateNotes($customer, $data['notes']);
        }

        return $customer;

    }

    /**
     * @param Customer $customer
     * @param array    $data
     *
     * @return Customer
     * @throws Exception
     */
    public function update(Customer $customer, array $data): Customer
    {
        /** @var CustomerUpdateService $service */
        $service = app(CustomerUpdateService::class);
        $service->setUser($this->user);

        return $service->update($customer, $data);
    }

    /**
     * @inheritDoc
     */
    public function updateNotes(Customer $customer, string $notes): void
    {
        $dbNote = $customer->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($customer);
        }
        $dbNote->text = trim($notes);
        $dbNote->save();
    }

    /**
     * @param Customer $customer
     *
     * @return Carbon|null
     */
    private function getFirstJournalDate(Customer $customer): ?Carbon
    {
        $query  = $customer->transactionJournals()->orderBy('date', 'ASC');
        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param Customer $customer
     *
     * @return Carbon|null
     */
    private function getFirstTransactionDate(Customer $customer): ?Carbon
    {
        // check transactions:
        $query = $customer->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'ASC');

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }

    /**
     * @param Customer   $customer
     * @param Collection $accounts
     *
     * @return Carbon|null
     */
    private function getLastJournalDate(Customer $customer, Collection $accounts): ?Carbon
    {
        $query = $customer->transactionJournals()->orderBy('date', 'DESC');

        if ($accounts->count() > 0) {
            $query->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('t.account_id', $accounts->pluck('id')->toArray());
        }

        $result = $query->first(['transaction_journals.*']);

        if (null !== $result) {
            return $result->date;
        }

        return null;
    }

    /**
     * @param Customer   $customer
     * @param Collection $accounts
     *
     * @return Carbon|null
     * @throws Exception
     */
    private function getLastTransactionDate(Customer $customer, Collection $accounts): ?Carbon
    {
        // check transactions:
        $query = $customer->transactions()
                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->orderBy('transaction_journals.date', 'DESC');
        if ($accounts->count() > 0) {
            // filter journals:
            $query->whereIn('transactions.account_id', $accounts->pluck('id')->toArray());
        }

        $lastTransaction = $query->first(['transaction_journals.*']);
        if (null !== $lastTransaction) {
            return new Carbon($lastTransaction->date);
        }

        return null;
    }
}
