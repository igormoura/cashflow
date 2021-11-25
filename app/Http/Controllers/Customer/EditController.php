<?php
/**
 * EditController.php
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

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ConsumerFormRequest;
use FireflyIII\Models\Customer;
use FireflyIII\Repositories\Customer\ConsumerRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{

    private AttachmentHelperInterface   $attachments;
    private ConsumerRepositoryInterface $repository;

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
                $this->repository  = app(ConsumerRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a customer.
     *
     * @param Request  $request
     * @param Customer $customer
     *
     * @return Factory|View
     */
    public function edit(Request $request, Customer $customer)
    {
        $subTitle = (string)trans('firefly.edit_consumer', ['name' => $customer->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('customers.edit.fromUpdate')) {
            $this->rememberPreviousUri('customers.edit.uri');
        }
        $request->session()->forget('customers.edit.fromUpdate');

        $preFilled = [
            'notes' => $request->old('notes') ?? $this->repository->getNoteText($customer),
        ];

        return prefixView('customers.edit', compact('customer', 'subTitle', 'preFilled'));
    }

    /**
     * Update customer.
     *
     * @param ConsumerFormRequest $request
     * @param Customer            $customer
     *
     * @return RedirectResponse|Redirector
     */
    public function update(ConsumerFormRequest $request, Customer $customer)
    {
        $data = $request->getConsumerData();
        $this->repository->update($customer, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_consumer', ['name' => $customer->name]));
        app('preferences')->mark();

        // store new attachment(s):
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
        $redirect = redirect($this->getPreviousUri('customers.edit.uri'));

        if (1 === (int)$request->get('return_to_edit')) {

            $request->session()->put('customers.edit.fromUpdate', true);

            $redirect = redirect(route('customers.edit', [$customer->id]));

        }

        return $redirect;
    }
}
