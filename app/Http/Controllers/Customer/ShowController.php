<?php
/**
 * ShowController.php
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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Customer;
use FireflyIII\Repositories\Customer\ConsumerRepositoryInterface;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 *
 * Class ShowController
 *
 */
class ShowController extends Controller
{
    use PeriodOverview;

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
        app('view')->share('showBudget', true);

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
     * Show a single customer.
     *
     * @param Request     $request
     * @param Customer    $customer
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Factory|View
     * @throws FireflyException
     */
    public function show(Request $request, Customer $customer, Carbon $start = null, Carbon $end = null)
    {
        /** @var Carbon $start */
        $start = $start ?? session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end          = $end ?? session('end', Carbon::now()->endOfMonth());
        $subTitleIcon = 'fa-bookmark';
        $page         = (int)$request->get('page');
        $attachments  = $this->repository->getAttachments($customer);
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $oldest       = $this->repository->firstUseDate($customer) ?? Carbon::now()->startOfYear();
        $periods      = $this->getConsumerPeriodOverview($customer, $oldest, $end);
        $path         = route('customers.show', [$customer->id, $start->format('Y-m-d'), $end->format('Y-m-d')]);
        $subTitle     = trans(
            'firefly.journals_in_period_for_consumer',
            ['name' => $customer->name, 'start' => $start->formatLocalized($this->monthAndDayFormat),
             'end'  => $end->formatLocalized($this->monthAndDayFormat),]
        );

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
                  ->withAccountInformation()
                  ->setConsumer($customer)->withBudgetInformation()->withConsumerInformation();

        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return prefixView('customers.show', compact('customer', 'attachments', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }

    /**
     * Show all transactions within a customer.
     *
     * @param Request  $request
     * @param Customer $customer
     *
     * @return Factory|View
     * @throws FireflyException
     */
    public function showAll(Request $request, Customer $customer)
    {
        // default values:
        $subTitleIcon = 'fa-bookmark';
        $page         = (int)$request->get('page');
        $pageSize     = (int)app('preferences')->get('listPageSize', 50)->data;
        $start        = null;
        $end          = null;
        $periods      = new Collection;

        $subTitle = (string)trans('firefly.all_journals_for_consumer', ['name' => $customer->name]);
        $first    = $this->repository->firstUseDate($customer);
        /** @var Carbon $start */
        $start       = $first ?? today(config('app.timezone'));
        $end         = today(config('app.timezone'));
        $path        = route('customers.show.all', [$customer->id]);
        $attachments = $this->repository->getAttachments($customer);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setLimit($pageSize)->setPage($page)
                  ->withAccountInformation()
                  ->setConsumer($customer)->withBudgetInformation()->withConsumerInformation();

        $groups = $collector->getPaginatedGroups();
        $groups->setPath($path);

        return prefixView('customers.show', compact('customer', 'attachments', 'groups', 'periods', 'subTitle', 'subTitleIcon', 'start', 'end'));
    }
}
