<?php

/*
 * 2021_12_01_152701_create_customer_tables.php
 * Copyright (c) 2021 james@firefly-iii.org
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCustomerTables.
 *
 * @codeCoverageIgnore
 */
class CreateCustomerTables extends Migration
{

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('customers');
        Schema::drop('customer_transaction');
        Schema::drop('customer_transaction_journal');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up(): void
    {
        $this->createCustomersTable();
        $this->customerTransactionTable();
        $this->customerTransactionJournalTable();

    }

    private function createCustomersTable(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create(
                'customers',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->integer('user_id', false, true);
                    $table->string('name', 1024);
                    $table->string('email', 1024);
                    $table->date('birthDate');
                    $table->string('gender', 1);
                    $table->integer('document');
                    $table->string('telephone');
                    $table->string('cellphone');
                    $table->string('address');
                    $table->string('addressNumber');
                    $table->string('addressComplement');
                    $table->string('zipCode');
                    $table->string('state');
                    $table->string('city');
                    $table->boolean('encrypted')->default(0);

                    // link user id to users table
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            );
        }
    }

    private function customerTransactionTable(): void
    {
        if (!Schema::hasTable('customer_transaction')) {
            Schema::create(
                'customer_transaction',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('customer_id', false, true);
                    $table->integer('transaction_id', false, true);

                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                    $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                }
            );
        }
    }

    private function customerTransactionJournalTable(): void
    {
        if (!Schema::hasTable('customer_transaction_journal')) {
            Schema::create(
                'customer_transaction_journal',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('customer_id', false, true);
                    $table->integer('transaction_journal_id', false, true);
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                    $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                }
            );
        }
    }
}
