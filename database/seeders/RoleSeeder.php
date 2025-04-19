<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Developer']);
        $sysAdmin = Role::create(['name' => 'System Administrator']);
        $custodian = Role::create(['name' => 'Custodian']);

        $sysAdmin->givePermissionTo([
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
            #BUDGET ALLOCAtION
            'view-budget',
            'create-budget',
            'edit-budget',
            'update-budget',
            #ACCOUNT CLASSIFICATION
            'view-account-class',
            'create-account-class',
            'edit-account-class',
            'delete-account-class',
            'view-trashed-account-class',
            'restore-trashed-account-class',
            #CATEGORY
            'view-category',
            'create-category',
            'edit-category',
            'delete-category',
            'view-trashed-category',
            'restore-trashed-category',
            #ITEM CLASS
            'view-item-class',
            'create-item-class',
            'edit-item-class',
            'delete-item-class',
            'view-trashed-item-class',
            'restore-trashed-item-class',
            #OFFICE
            'view-office',
            'create-office',
            'edit-office',
            'delete-office',
            #PRODUCTS
            'view-product',
            'create-product',
            'edit-product',
            'modify-product',
            'delete-product',
            'print-product',
            #PRODUCT PRICE
            'view-product-price',
            'print-product-price',
            #PRODUCT EXCEPTIONS FOR QUANTITY ADJUSTMENT
            'view-product-exceptions',
            'create-product-exceptions',
            'delete-product-exceptions',
            #PPMP
            'view-ppmp',
            #OFFICE PPMP
            'view-create-ppmp',
            'create-office-ppmp',
            'view-office-ppmp',
            'delete-office-ppmp',
            'print-office-ppmp',
            'create-office-particular',
            'edit-office-particular',
            'delete-office-particular',
            #OFFICE PPMP LIST
            'view-office-ppmp-draft-list',
            'view-office-ppmp-approved-list',
            #APP
            'view-office-app-draft-list',
            'view-office-app-approved-list',
            'create-app',
            'view-app',
            'delete-app',
            'create-app-particular',
            'edit-app-particular',
            'delete-app-particular',
            #PURCHASE REQUEST
            'view-purchase-request',
            'view-procurement-basis',
            'create-purchase-request',
            'view-pr-list',
            'view-pr-transaction',
            'print-pr-transaction',
            'edit-pr-particular',
            'approved-pr-particular',
            'delete-pr-particular',
            'approved-pr-particular-all',
            'reject-pr-particular-all',
            #IAR
            'view-iar',
            'create-iar-transacation',
            'view-iar-transaction',
            'edit-iar-particular',
            'accept-iar-particular',
            'reject-iar-particular',
            'accept-iar-particular-all',
            'reject-iar-particular-all',
            'view-all-transaction',
            'view-all-completed-transaction',
            #RIS
            'view-ris',
            'create-ris-transaction',
            #INVENTORY
            'view-inventory',
            'edit-product-threshold',
            'edit-product-inventory',
            'view-stock-card',
            'view-expired-products',
        ]);

        $custodian->givePermissionTo([
            #BUDGET ALLOCAtION
            'view-budget',
            'create-budget',
            'edit-budget',
            'update-budget',
            #ACCOUNT CLASSIFICATION
            'view-account-class',
            'create-account-class',
            'edit-account-class',
            'delete-account-class',
            'view-trashed-account-class',
            'restore-trashed-account-class',
            #CATEGORY
            'view-category',
            'create-category',
            'edit-category',
            'delete-category',
            'view-trashed-category',
            'restore-trashed-category',
            #ITEM CLASS
            'view-item-class',
            'create-item-class',
            'edit-item-class',
            'delete-item-class',
            'view-trashed-item-class',
            'restore-trashed-item-class',
            #OFFICE
            'view-office',
            'create-office',
            'edit-office',
            'delete-office',
            #PRODUCTS
            'view-product',
            'create-product',
            'edit-product',
            'modify-product',
            'delete-product',
            'print-product',
            #PRODUCT PRICE
            'view-product-price',
            'print-product-price',
            #PRODUCT EXCEPTIONS FOR QUANTITY ADJUSTMENT
            'view-product-exceptions',
            'create-product-exceptions',
            'delete-product-exceptions',
            #PPMP
            'view-ppmp',
            #OFFICE PPMP
            'view-create-ppmp',
            'create-office-ppmp',
            'view-office-ppmp',
            'delete-office-ppmp',
            'print-office-ppmp',
            'create-office-particular',
            'edit-office-particular',
            'delete-office-particular',
            #OFFICE PPMP LIST
            'view-office-ppmp-draft-list',
            'view-office-ppmp-approved-list',
            #APP
            'view-office-app-draft-list',
            'view-office-app-approved-list',
            'create-app',
            'view-app',
            'delete-app',
            'create-app-particular',
            'edit-app-particular',
            'delete-app-particular',
            #PURCHASE REQUEST
            'view-purchase-request',
            'view-procurement-basis',
            'create-purchase-request',
            'view-pr-list',
            'view-pr-transaction',
            'print-pr-transaction',
            'edit-pr-particular',
            'approved-pr-particular',
            'delete-pr-particular',
            'approved-pr-particular-all',
            'reject-pr-particular-all',
            #IAR
            'view-iar',
            'create-iar-transacation',
            'view-iar-transaction',
            'edit-iar-particular',
            'accept-iar-particular',
            'reject-iar-particular',
            'accept-iar-particular-all',
            'reject-iar-particular-all',
            'view-all-transaction',
            'view-all-completed-transaction',
            #RIS
            'view-ris',
            'create-ris-transaction',
            #INVENTORY
            'view-inventory',
            'edit-product-threshold',
            'edit-product-inventory',
            'view-stock-card',
            'view-expired-products',
        ]);
    }
}
