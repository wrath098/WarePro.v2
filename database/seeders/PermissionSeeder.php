<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            #USER
            'create-user',
            'edit-user',
            'delete-user',
            
            #ROLES
            'create-role',
            'edit-role',
            'delete-role',

            #PERMISSION
            'create-permission',
            'edit-permission',
            'delete-permission',

            #COMPONENTS
            #PROPOSED BUDGET
            'view-proposed-budget',
            'create-new-budget',
            'edit-proposed-budget',

            #ACCOUNT CLASSIFICATION
            'view-account-class',
            'view-trashed-account-class',
            'create-account-class',
            'edit-account-class',
            'delete-account-class',

            #CATEGORIES
            'view-category',
            'view-trashed-category',
            'create-category',
            'edit-category',
            'delete-category',

            #ITEM CLASS
            'view-item-class',
            'view-trashed-item-class',
            'create-item-class',
            'edit-item-class',
            'delete-item-class',

            #OFFICE
            'view-office',
            'create-office',
            'edit-office',
            'delete-office',

            #PRODUCTS
            #ITEM LIST
            'view-product-list',
            'create-product-item',
            'print-product-list',
            'view-trashed-product-items',
            'edit-product-item',
            'modify-product-item',
            'delete-product-item',

            #PRODUCT PRICE
            'view-price-list',
            'print-price-list',

            #PRODUCT QUANTITY EXEMPTION
            'view-product-exemption',
            'create-product-exemption',
            'delete-product-exemption',

            #PROJECT PROCUREMENT MANAGEMENT PLAN
            #OFFICE PPMP
            'create-office-ppmp',
            'view-office-ppmp',
            'view-office-ppmp-list',
            'delete-office-ppmp',
            'print-office-ppmp',

            #OFFICE PPMP PARTICULARS
            'create-office-ppmp-particular',
            'edit-office-ppmp-particular',
            'delete-office-ppmp-particular',

            #ANNUAL PROCUREMENT PLAN
            'consolidate-office-ppmp',
            'view-app-list',
            'view-app',
            'delete-app',
            'print-app',
            'print-app-summary-overview',

            #ANNUAL PROCUREMENT PLAN PARTICULARS
            'add-app-particular',
            'edit-app-particular',
            'delete-app-particular',
            'confirm-app-finalization',

            #PURCHASE REQUEST
            #PROCUREMENT BASIS
            'view-procurement-basis',
            'view-purchase-request',

            #CREATE PURCHASE REQUEST
            'create-purchase-request',

            #PENDING FOR APPROVAL OF PURCHASE REQUEST
            'view-purchase-request-list',
            'print-purchase-request',

            'edit-pr-particular',
            'accept-pr-particular',
            'reject-pr-particular',
            'accept-all-pr-particular',
            'reject-all-pr-particular',

            #PURCHASE REQUEST
            'view-purchase-order',

            #IAR
            #RECEIVING
            'view-iar-transaction-pending',
            'view-iar-transaction-all',
            'view-iar-transaction',
            'collect-iar-transactions',

            #IAR PARTICULARS
            'update-iar-particular',
            'accept-iar-particular',
            'reject-iar-particular',
            'accept-all-iar-particular',
            'reject-all-iar-particular',

            #RIS
            'create-ris-transaction',
            'view-ris-transactions',

            #INVENTORY
            'view-products-inventory',

            #STOCK CARD
            'view-product-stock-card',

            #EXPIRED PRODUCTS
            'monitor-expiring-products',
         ];
 
         foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
          }
    }
}
