<?php
/**
 * NoCustomerRepository.php
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
 * Class NoCustomerRepository
 */
class NoCustomerRepository implements NoCustomerRepositoryInterface
{
    private User $user;

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have no customer set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCustomer();
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId         = (int)$journal['currency_id'];
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            // info about the non-existent customer:
            $array[$currencyId]['customers'][0] = $array[$currencyId]['customers'][0] ?? [
                    'id'                   => 0,
                    'name'                 => (string)trans('firefly.noCustomer'),
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][0]['transaction_journals'][$journalId]
                       = [
                'amount' => app('steam')->negative($journal['amount']),
                'date'   => $journal['date'],
            ];

        }

        return $array;
    }

    /**
     * This method returns a list of all the deposit transaction journals (as arrays) set in that period
     * which have no customer set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->withoutCustomer();
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId         = (int)$journal['currency_id'];
            $array[$currencyId] = $array[$currencyId] ?? [
                    'customers'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the non-existent customer:
            $array[$currencyId]['customers'][0] = $array[$currencyId]['customers'][0] ?? [
                    'id'                   => 0,
                    'name'                 => (string)trans('firefly.noCustomer'),
                    'transaction_journals' => [],
                ];
            // add journal to array:
            // only a subset of the fields.
            $journalId = (int)$journal['transaction_journal_id'];
            $array[$currencyId]['customers'][0]['transaction_journals'][$journalId]
                       = [
                'amount' => app('steam')->positive($journal['amount']),
                'date'   => $journal['date'],
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
     * Sum of withdrawal journals in period without a customer, grouped per currency. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutCustomer();

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
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
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($journal['amount'] ?? '0'));
        }

        return $array;
    }

    /**
     * Sum of income journals in period without a customer, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->withoutCustomer();

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
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
     * @inheritDoc
     */
    public function sumTransfers(Carbon $start, Carbon $end, ?Collection $accounts = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::TRANSFER])->withoutCustomer();

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
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
}
