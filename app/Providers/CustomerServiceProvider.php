<?php
/**
 * CustomerServiceProvider.php
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

namespace FireflyIII\Providers;

use FireflyIII\Repositories\Customer\CustomerRepository;
use FireflyIII\Repositories\Customer\CustomerRepositoryInterface;
use FireflyIII\Repositories\Customer\NoCustomerRepository;
use FireflyIII\Repositories\Customer\NoCustomerRepositoryInterface;
use FireflyIII\Repositories\Customer\OperationsRepository;
use FireflyIII\Repositories\Customer\OperationsRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * @codeCoverageIgnore
 * Class CustomerServiceProvider.
 */
class CustomerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CustomerRepositoryInterface::class,
            static function (Application $app) {
                /** @var CustomerRepository $repository */
                $repository = app(CustomerRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            OperationsRepositoryInterface::class,
            static function (Application $app) {
                /** @var OperationsRepository $repository */
                $repository = app(OperationsRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            NoCustomerRepositoryInterface::class,
            static function (Application $app) {
                /** @var NoCustomerRepository $repository */
                $repository = app(NoCustomerRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
