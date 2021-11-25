<?php
/**
 * OperationsRepository.php
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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 *
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    private User $user;

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified customer set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * First currency, then customers.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $customers && $customers->count() > 0) {
            $collector->setCustomers($customers);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $collector->setCustomers($this->getCustomers());
        }
        $collector->withCustomerInformation()->withAccountInformation()->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $customerId   = (int)$journal['customer_id'];
            $customerName = (string)$journal['customer_name'];

            // catch "no customer" entries.
            if (0 === $customerId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the customers:
            $array[$currencyId]['customers'][$customerId] = $array[$currencyId]['customers'][$customerId] ?? [
                    'id'                   => $customerId,
                    'name'                 => $customerName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                         = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][$customerId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->negative($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'budget_name'              => $journal['budget_name'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_id'   => $journal['destination_account_id'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }

    /**
     * This method returns a list of all the deposit transaction journals (as arrays) set in that period
     * which have the specified customer set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $customers && $customers->count() > 0) {
            $collector->setCustomers($customers);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $collector->setCustomers($this->getCustomers());
        }
        $collector->withCustomerInformation()->withAccountInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $customerId   = (int)$journal['customer_id'];
            $customerName = (string)$journal['customer_name'];

            // catch "no customer" entries.
            if (0 === $customerId) {
                $customerName = (string)trans('firefly.no_customer');
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the customers:
            $array[$currencyId]['customers'][$customerId] = $array[$currencyId]['customers'][$customerId] ?? [
                    'id'                   => $customerId,
                    'name'                 => $customerName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                         = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][$customerId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->positive($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'destination_account_id'   => $journal['destination_account_id'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Sum of withdrawal journals in period for a set of customers, grouped per currency. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $customers = $this->getCustomers();
        }
        $collector->setCustomers($customers);
        $collector->withCustomerInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => (int)$journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($journal['amount']));
        }

        return $array;
    }

    /**
     * Sum of income journals in period for a set of customers, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $customers = $this->getCustomers();
        }
        $collector->setCustomers($customers);
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->positive($journal['amount']));
        }

        return $array;
    }

    /**
     * Sum of income journals in period for a set of customers, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function sumTransfers(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)
                  ->setTypes([TransactionType::TRANSFER]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $customers = $this->getCustomers();
        }
        $collector->setCustomers($customers);
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->positive($journal['amount']));
        }

        return $array;
    }

    /**
     * Returns a list of all the customers belonging to a user.
     *
     * @return Collection
     */
    private function getCustomers(): Collection
    {
        return $this->user->customers()->get();
    }

    /**
     * @inheritDoc
     */
    public function listTransferredIn(Carbon $start, Carbon $end, Collection $accounts, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::TRANSFER])
                  ->setDestinationAccounts($accounts)->excludeSourceAccounts($accounts);
        if (null !== $customers && $customers->count() > 0) {
            $collector->setCustomers($customers);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $collector->setCustomers($this->getCustomers());
        }
        $collector->withCustomerInformation()->withAccountInformation()->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $customerId   = (int)$journal['customer_id'];
            $customerName = (string)$journal['customer_name'];

            // catch "no customer" entries.
            if (0 === $customerId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the customers:
            $array[$currencyId]['customers'][$customerId] = $array[$currencyId]['customers'][$customerId] ?? [
                    'id'                   => $customerId,
                    'name'                 => $customerName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                         = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][$customerId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->positive($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'customer_name'              => $journal['customer_name'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_id'   => $journal['destination_account_id'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function listTransferredOut(Carbon $start, Carbon $end, Collection $accounts, ?Collection $customers = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::TRANSFER])
                  ->setSourceAccounts($accounts)->excludeDestinationAccounts($accounts);
        if (null !== $customers && $customers->count() > 0) {
            $collector->setCustomers($customers);
        }
        if (null === $customers || (null !== $customers && 0 === $customers->count())) {
            $collector->setCustomers($this->getCustomers());
        }
        $collector->withCustomerInformation()->withAccountInformation()->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $customerId   = (int)$journal['customer_id'];
            $customerName = (string)$journal['customer_name'];

            // catch "no customer" entries.
            if (0 === $customerId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the customers:
            $array[$currencyId]['customers'][$customerId] = $array[$currencyId]['customers'][$customerId] ?? [
                    'id'                   => $customerId,
                    'name'                 => $customerName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                         = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][$customerId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->negative($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'customer_name'              => $journal['customer_name'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_id'   => $journal['destination_account_id'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }
}
