<?php
/**
 * CustomerFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\Customer;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CustomerFormRequest.
 */
class CustomerFormRequest extends FormRequest
{
    use ConvertsDataTypes, ChecksLogin;

    /**
     * Get information for the controller.
     *
     * @return array
     */
    public function getCustomerData(): array
    {
        return [
            'name'  => $this->string('name'),
            'notes' => $this->stringWithNewlines('notes'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $nameRule = 'required|between:1,100|uniqueObjectForUser:customers,name';
        /** @var Customer $customer */
        $customer = $this->route()->parameter('customer');

        if (null !== $customer) {
            $nameRule = 'required|between:1,100|uniqueObjectForUser:customers,name,' . $customer->id;
        }

        // fixed
        return [
            'name' => $nameRule,
        ];
    }
}
