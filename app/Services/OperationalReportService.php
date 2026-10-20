<?php

namespace App\Services;

use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OperationalReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(User $user, array $filters = []): array
    {
        $businessOwners = $this->businessOwnerQuery($user, $filters);
        $customers = $this->customerQuery($user, $filters);
        $fees = $this->feeQuery($user, $filters);
        $payments = $this->paymentQuery($user, $filters);
        $orders = $this->orderQuery($user, $filters);

        return [
            'summary' => [
                'business_owner_count' => (clone $businessOwners)->count(),
                'customer_count' => (clone $customers)->count(),
                'vip_customer_count' => (clone $customers)->where('is_vip', true)->count(),
                'fee_total' => (float) (clone $fees)->sum('amount'),
                'payment_total' => (float) (clone $payments)->sum('amount'),
                'order_count' => (clone $orders)->count(),
                'order_total' => (float) (clone $orders)->sum('total'),
            ],
            'business_owners' => (clone $businessOwners)->with(['partner', 'territory'])->latest()->limit(25)->get(),
            'customers' => (clone $customers)->with(['businessOwner', 'partner'])->latest()->limit(25)->get(),
            'fees' => (clone $fees)->with(['businessOwner', 'partner', 'customer'])->latest()->limit(25)->get(),
            'payments' => (clone $payments)->with(['fee.businessOwner', 'order.businessOwner'])->latest()->limit(25)->get(),
            'orders' => (clone $orders)->with(['businessOwner', 'partner', 'customer'])->latest()->limit(25)->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<BusinessOwner>
     */
    public function businessOwnerQuery(User $user, array $filters = []): Builder
    {
        $query = BusinessOwner::query()->visibleTo($user);

        $this->applyCommonScopeFilters($query, $filters, hasCustomerCategory: false);
        $this->applyDateFilters($query, $filters, 'created_at');

        if (filled($filters['territory_id'] ?? null)) {
            $query->where('territory_id', $filters['territory_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Customer>
     */
    public function customerQuery(User $user, array $filters = []): Builder
    {
        $query = Customer::query()->visibleTo($user);

        $this->applyCommonScopeFilters($query, $filters);
        $this->applyDateFilters($query, $filters, 'created_at');

        if (filled($filters['territory_id'] ?? null)) {
            $query->whereHas('businessOwner', fn (Builder $query): Builder => $query->where('territory_id', $filters['territory_id']));
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Fee>
     */
    public function feeQuery(User $user, array $filters = []): Builder
    {
        $query = Fee::query()->visibleTo($user);

        $this->applyCommonScopeFilters($query, $filters);
        $this->applyDateFilters($query, $filters, 'created_at');

        if (filled($filters['territory_id'] ?? null)) {
            $query->whereHas('businessOwner', fn (Builder $query): Builder => $query->where('territory_id', $filters['territory_id']));
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Payment>
     */
    public function paymentQuery(User $user, array $filters = []): Builder
    {
        $query = Payment::query()->visibleTo($user);

        $this->applyDateFilters($query, $filters, 'created_at');

        if (filled($filters['partner_id'] ?? null)) {
            $query->where(function (Builder $query) use ($filters): void {
                $query
                    ->whereHas('fee', fn (Builder $query): Builder => $query->where('partner_id', $filters['partner_id']))
                    ->orWhereHas('order', fn (Builder $query): Builder => $query->where('partner_id', $filters['partner_id']));
            });
        }

        if (filled($filters['business_owner_id'] ?? null)) {
            $query->where(function (Builder $query) use ($filters): void {
                $query
                    ->whereHas('fee', fn (Builder $query): Builder => $query->where('business_owner_id', $filters['business_owner_id']))
                    ->orWhereHas('order', fn (Builder $query): Builder => $query->where('business_owner_id', $filters['business_owner_id']));
            });
        }

        if (filled($filters['customer_category'] ?? null)) {
            $query->where(function (Builder $query) use ($filters): void {
                $query
                    ->whereHas('fee.customer', fn (Builder $query): Builder => $query->where('category', $filters['customer_category']))
                    ->orWhereHas('order.customer', fn (Builder $query): Builder => $query->where('category', $filters['customer_category']));
            });
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        $this->applyOrderProductFilters($query, $filters, relation: 'order');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    public function orderQuery(User $user, array $filters = []): Builder
    {
        $query = Order::query()->visibleTo($user);

        $this->applyCommonScopeFilters($query, $filters);
        $this->applyDateFilters($query, $filters, 'created_at');

        if (filled($filters['territory_id'] ?? null)) {
            $query->whereHas('businessOwner', fn (Builder $query): Builder => $query->where('territory_id', $filters['territory_id']));
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        $this->applyOrderProductFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<object>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonScopeFilters(Builder $query, array $filters, bool $hasCustomerCategory = true): void
    {
        if (filled($filters['partner_id'] ?? null)) {
            $query->where('partner_id', $filters['partner_id']);
        }

        if (filled($filters['business_owner_id'] ?? null)) {
            $query->where('business_owner_id', $filters['business_owner_id']);
        }

        if ($hasCustomerCategory && filled($filters['customer_category'] ?? null)) {
            if ($query->getModel() instanceof Customer) {
                $query->where('category', $filters['customer_category']);
            } else {
                $query->whereHas('customer', fn (Builder $query): Builder => $query->where('category', $filters['customer_category']));
            }
        }
    }

    /**
     * @param  Builder<object>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilters(Builder $query, array $filters, string $column): void
    {
        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    /**
     * @param  Builder<object>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyOrderProductFilters(Builder $query, array $filters, string $relation = 'items'): void
    {
        if (blank($filters['product_type_id'] ?? null)) {
            return;
        }

        $itemsRelation = $relation === 'order' ? 'order.items.product' : 'items.product';

        $query->whereHas($itemsRelation, function (Builder $query) use ($filters): Builder {
            return $query->where('product_type_id', $filters['product_type_id']);
        });
    }
}
