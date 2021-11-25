<?php
/**
 * DeleteController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Customer;
use FireflyIII\Repositories\Customer\ConsumerRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
{
    /** @var ConsumerRepositoryInterface The customer repository */
    private $repository;

    /**
     * ConsumerController constructor.
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
                $this->repository = app(ConsumerRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Delete a customer.
     *
     * @param Customer $customer
     *
     * @return Factory|View
     */
    public function delete(Customer $customer)
    {
        $subTitle = (string)trans('firefly.delete_consumer', ['name' => $customer->name]);

        // put previous url in session
        $this->rememberPreviousUri('customers.delete.uri');

        return prefixView('customers.delete', compact('customer', 'subTitle'));
    }

    /**
     * Destroy a customer.
     *
     * @param Request $request
     * @param Customer $customer
     *
     * @return RedirectResponse|Redirector
     */
    public function destroy(Request $request, Customer $customer)
    {
        $name = $customer->name;
        $this->repository->destroy($customer);

        $request->session()->flash('success', (string)trans('firefly.deleted_consumer', ['name' => $name]));
        app('preferences')->mark();

        return redirect($this->getPreviousUri('customers.delete.uri'));
    }
}
