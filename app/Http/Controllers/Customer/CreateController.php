<?php
/**
 * CreateController.php
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
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\CustomerFormRequest;
use FireflyIII\Repositories\Customer\CustomerRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private AttachmentHelperInterface   $attachments;
    private CustomerRepositoryInterface $repository;

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
                $this->repository  = app(CustomerRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create customer.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function create(Request $request)
    {

        if (true !== session('customers.create.fromStore')) {
            $this->rememberPreviousUri('customers.create.uri');
        }
        $request->session()->forget('customers.create.fromStore');
        $subTitle = (string)trans('firefly.create_new_category');

        return prefixView('customers.create', compact('subTitle'));
    }

    /**
     * Store new customer.
     *
     * @param CustomerFormRequest $request
     *
     * @return $this|RedirectResponse|Redirector
     * @throws FireflyException
     */
    public function store(CustomerFormRequest $request)
    {
        $data     = $request->getCustomerData();
        $customer = $this->repository->store($data);

        $request->session()->flash('success', (string)trans('firefly.stored_customer', ['name' => $customer->name]));
        app('preferences')->mark();

        // store attachment(s):
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($customer, $files);
        }
        
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info', (string)trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        $redirect = redirect(route('customers.index'));
        if (1 === (int)$request->get('create_another')) {

            $request->session()->put('customers.create.fromStore', true);

            $redirect = redirect(route('customers.create'))->withInput();

        }

        return $redirect;
    }
}
