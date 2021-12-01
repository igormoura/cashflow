<?php
/**
 * IndexController.php
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

namespace FireflyIII\Http\Controllers\Customer;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Customer;
use FireflyIII\Repositories\Customer\CustomerRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    /** @var CostumerRepositoryInterface The customer repository */
    private $repository;

    /**
     * CustomerController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.customers'));
                app('view')->share('mainTitleIcon', 'fa-bookmark');
                $this->repository = app(CustomerRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show all customers.
     *
     * @param Request $request
     *
     * @return Factory|View
     * @throws FireflyException
     */
    public function index(Request $request)
    {
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection = $this->repository->getCustomers();
        $total      = $collection->count();
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);

        $collection->each(
            function (Customer $customer) {
                $customer->lastActivity = $this->repository->lastUseDate($customer, new Collection);
            }
        );

        // paginate customers
        $customers = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $customers->setPath(route('customers.index'));

        return prefixView('customers.index', compact('customers'));
    }

}
