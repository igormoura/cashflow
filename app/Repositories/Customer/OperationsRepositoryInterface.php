<?php
/**
 * OperationsRepositoryInterface.php
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
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface OperationsRepositoryInterface
 *
 */
interface OperationsRepositoryInterface
{
    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified customer set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array;

    /**
     * This method returns a list of all the transfer transaction journals (as arrays) set in that period
     * which have the specified customer set to them, transferred INTO the listed accounts.
     * It excludes any transfers between the listed accounts.
     * It's grouped per currency, with as few details in the array as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function listTransferredIn(Carbon $start, Carbon $end, Collection $accounts, ?Collection $customers = null): array;

    /**
     * This method returns a list of all the transfer transaction journals (as arrays) set in that period
     * which have the specified customer set to them, transferred FROM the listed accounts.
     * It excludes any transfers between the listed accounts.
     * It's grouped per currency, with as few details in the array as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function listTransferredOut(Carbon $start, Carbon $end, Collection $accounts, ?Collection $customers = null): array;

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
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

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
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array;

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
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array;

    /**
     * Sum of transfers in period for a set of customers, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $customers
     *
     * @return array
     */
    public function sumTransfers(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $customers = null): array;
}
