<script setup lang="ts">
import SidebarLink from '@/Components/Buttons/SidebarLink.vue';
import SidebarDropdown from '@/Components/Buttons/SidebarDropdown.vue';
import SubSidebarLink from '@/Components/Buttons/SubSidebarLink.vue';
import ArrowDown from '@/Components/Svgs/ArrowDown.vue';
import Inspect from '@/Components/Svgs/Inspect.vue';
import ArrowHeadRight from '@/Components/Svgs/ArrowHeadRight.vue';
import Stock from '@/Components/Svgs/Stock.vue';
import useAuthPermission from '@/Composables/useAuthPermission';

const { hasAnyRole, hasPermission , hasAnyPermission} = useAuthPermission();

const componentPermissions = [
    'view-office',
    'view-proposed-budget',
    'view-account-class',
    'view-category',
    'view-item-class'
];

const productPermissions = [
    'view-product-list',
    'view-price-list',
    'view-product-exemption'
];

const ppmpPermissions = [
    'create-office-ppmp',
    'view-office-ppmp-list',
    'view-app-list'
];

const procurementPermissions = [
    'view-purchase-order',
    'view-purchase-request-list',
];

const inventoryPermissions = [
    'view-iar-transaction-pending',
    'view-iar-transaction-all',
    'view-ris-transactions',
    'create-ris-transaction',
    'view-products-inventory',
    'monitor-expiring-products',
    'view-product-stock-card',
];

const components = hasAnyPermission(componentPermissions) || hasAnyRole(['Developer']);
const products = hasAnyPermission(productPermissions) || hasAnyRole(['Developer']);
const ppmp = hasAnyPermission(ppmpPermissions) || hasAnyRole(['Developer']);
const procurement = hasAnyPermission(procurementPermissions) || hasAnyRole(['Developer']);
const inventory = hasAnyPermission(inventoryPermissions) || hasAnyRole(['Developer']);
const officeUser = hasAnyRole(['Office User']);
</script>

<template>
    <div class="lg:block">
        <aside class="fixed w-80 h-[calc(100vh-8rem)] z-50 overflow-y-auto select-none top-24 lg:left-8 transition-all rounded-lg p-2 bg-zinc-300">
            <nav class="text-sm text-gray-700 mb-5">
                <div class="overflow-hidden py-3 px-3 h-full">
                    <ul class="space-y-2">
                        <li>
                            <ol>
                                <li>
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">NAVIGATION</span>
                                    </p>
                                </li>
                                <li>
                                    <SidebarLink :href="route('dashboard')" :active="$page.component == 'Dashboard'">
                                        <svg 
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': $page.component == 'Dashboard'}"
                                            fill="currentColor"
                                            aria-hidden="true"
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" d="M15.21 2H8.75A6.76 6.76 0 0 0 2 8.75v6.5A6.76 6.76 0 0 0 8.75 22h6.5A6.76 6.76 0 0 0 22 15.25v-6.5A6.76 6.76 0 0 0 15.21 2M8.43 16.23a.8.8 0 1 1-1.6 0v-5.1a.8.8 0 0 1 1.6 0zm4.45 0a.8.8 0 1 1-1.6 0V7.78a.8.8 0 0 1 1.6 0zm4.21 0a.8.8 0 1 1-1.6 0V9.82a.8.8 0 0 1 1.6 0z"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">DASHBOARD</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="officeUser">
                                    <SidebarLink :href="route('product.display.active')" :active="route().current('product.display.active')">
                                        <svg
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': route().current('product.display.active')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" fill-rule="evenodd" d="M15 4H9v16h6zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3zM4 4h3v16H4a2 2 0 0 1-2-2V6c0-1.1.9-2 2-2" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Product List</span>
                                    </SidebarLink>
                                </li>
                            </ol>
                        </li>
                        <li v-if="components || products">
                            <ol class="mt-4">
                                <li class="pt-2">
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">CORE</span>
                                    </p>
                                </li>
                                <li v-if="components" class="mb-1">
                                    <SidebarDropdown 
                                        :active="route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display') || route().current('general.fund.editFundAllocation')"
                                        >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display') || route().current('general.fund.editFundAllocation')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            viewBox="0 0 20 20" 
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">COMPONENTS</span>
                                        <ArrowDown :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display') || route().current('general.fund.editFundAllocation')}" />
                                        <template #dropdown-items>
                                            <li v-if="hasPermission('view-proposed-budget') || hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('general.fund.display')" :active="route().current('general.fund.display') || route().current('general.fund.editFundAllocation')">
                                                    <ArrowHeadRight :class="{ 'text-white ': route().current('general-servies-fund')}"/>
                                                    Proposed Budget 
                                                </SubSidebarLink>
                                            </li>
                                            <li v-if="hasPermission('view-account-class') || hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('fund.display.all')" :active="route().current('fund.display.all') || route().current('category.display.active')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('fund.display.all') || route().current('category.display.active')}"/>
                                                    Account Classification
                                                </SubSidebarLink>
                                            </li>
                                            <li v-if="hasPermission('view-item-class') || hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('item.display.active')" :active="route().current('item.display.active')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('item.display.active')}"/>
                                                    Item Classes
                                                </SubSidebarLink>
                                            </li>
                                            <li v-if="hasPermission('view-office') || hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('office.display.active')" :active="route().current('office.display.active')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('office.display.active')}"/>
                                                    Offices
                                                </SubSidebarLink>
                                            </li>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                                <li v-if="products">
                                    <SidebarDropdown :active="route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')">
                                        <svg
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" fill-rule="evenodd" d="M15 4H9v16h6zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3zM4 4h3v16H4a2 2 0 0 1-2-2V6c0-1.1.9-2 2-2" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">PRODUCTS</span>
                                        <ArrowDown :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}" />
                                        
                                        <template #dropdown-items>
                                            <li v-if="hasPermission('view-product-list') ||  hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('product.display.active')" :active="route().current('product.display.active')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active')}"/>
                                                    Item List
                                                </SubSidebarLink>
                                            </li>
                                            <li v-if="hasPermission('view-price-list') ||  hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('product.display.active.pricelist')" :active="route().current('product.display.active.pricelist')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active.pricelist')}"/>
                                                    Price Timeline
                                                </SubSidebarLink>
                                            </li>
                                            <li v-if="hasPermission('view-product-exemption') ||  hasAnyRole(['Developer'])">
                                                <SubSidebarLink :href="route('product.unmodified.list')" :active="route().current('product.unmodified.list')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.unmodified.list')}"/>
                                                    Fixed Quantity
                                                </SubSidebarLink>
                                            </li>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                            </ol>
                        </li>
                        <li>
                            <ol class="mt-4">
                                <li v-if="ppmp" class="pt-2">
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">ANNUAL PROCUREMENT PLAN (APP)</span>
                                    </p>
                                </li>
                                <li class="mb-1">
                                    <SidebarLink 
                                        v-if="hasPermission('create-office-ppmp') ||  hasAnyRole(['Developer'])"
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('import.ppmp.index') || route().current('indiv.ppmp.show') }"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M17 23v-3h-3v-2h3v-3h2v3h3v2h-3v3zm-9-6q.425 0 .713-.288T9 16t-.288-.712T8 15t-.712.288T7 16t.288.713T8 17m0-4q.425 0 .713-.288T9 12t-.288-.712T8 11t-.712.288T7 12t.288.713T8 13m0-4q.425 0 .713-.288T9 8t-.288-.712T8 7t-.712.288T7 8t.288.713T8 9m3 4h6v-2h-6zm0-4h6V7h-6zM5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v8.825q-.675-.4-1.437-.6t-1.563-.2q-1.325 0-2.475.525T13.55 15H11v2h1.35q-.175.475-.262.975T12 19q0 .5.075 1t.25 1z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">CREATE OFFICE PPMP</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="hasPermission('view-office-ppmp-list') ||  hasAnyRole(['Developer'])" class="mb-1">
                                    <SidebarLink 
                                        v-if="hasPermission('create-office-ppmp') ||  hasAnyRole(['Developer'])"
                                        :href="route('indiv.ppmp.type')" 
                                        :active="route().current('indiv.ppmp.type')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white" 
                                            :class="{'text-white': route().current('indiv.ppmp.type')}"
                                            fill="currentColor"
                                            aria-hidden="true"
                                            viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M10.501 11.724L.631 7.16c-.841-.399-.841-1.014 0-1.376l9.87-4.563c.841-.399 2.194-.399 2.998 0l9.87 4.563c.841.398.841 1.014 0 1.376l-9.87 4.563c-.841.362-2.194.362-2.998 0zm0 5.468l-9.87-4.563c-.841-.399-.841-1.014 0-1.376l3.363-1.558l6.507 3.006c.841.398 2.194.398 2.998 0l6.507-3.006l3.363 1.558c.841.398.841 1.014 0 1.376l-9.87 4.563c-.841.398-2.194.398-2.998 0m0 0L.631 12.63c-.841-.399-.841-1.014 0-1.376l3.363-1.558l6.507 3.006c.841.398 2.194.398 2.998 0l6.507-3.006l3.363 1.558c.841.398.841 1.014 0 1.376l-9.87 4.563c-.841.398-2.194.398-2.998 0m0 5.613l-9.87-4.563c-.841-.398-.841-1.014 0-1.376l3.436-1.593l6.398 2.97c.84.398 2.193.398 2.997 0l6.398-2.97l3.436 1.593c.841.4.841 1.014 0 1.376l-9.87 4.563c-.768.362-2.12.362-2.925 0"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">OFFICE PPMP</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="hasPermission('view-app-list') ||  hasAnyRole(['Developer'])">
                                    <SidebarDropdown :active="route().current('conso.ppmp.type') || route().current('conso.ppmp.show')">
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': route().current('conso.ppmp.type') || route().current('conso.ppmp.show')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            viewBox="0 0 16 16">
                                            <path fill="currentColor" d="M0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6zM2 3a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 0-1h-11A.5.5 0 0 0 2 3m2-2a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7A.5.5 0 0 0 4 1"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">CONSOLIDATED PPMP</span>
                                        <ArrowDown :class="{'text-white': route().current('conso.ppmp.type') || route().current('conso.ppmp.show')}" />

                                        <template #dropdown-items>
                                            <li>
                                                <SubSidebarLink :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" :active="route().current('conso.ppmp.type', { type: 'consolidated' , status: 'draft'}) || route().current('conso.ppmp.show')">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('conso.ppmp.type', { type: 'consolidated' , status: 'draft'}) || route().current('conso.ppmp.show')}"/>
                                                    Draft
                                                </SubSidebarLink>
                                            </li>
                                            <li>
                                                <SubSidebarLink :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'approved'})" :active="route().current('conso.ppmp.type', { type: 'consolidated' , status: 'approved'})">
                                                    <ArrowHeadRight :class="{ 'text-white' : route().current('conso.ppmp.type', { type: 'consolidated' , status: 'approved'})}"/>
                                                    Approved
                                                </SubSidebarLink>
                                            </li>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                            </ol>
                        </li>
                        <li v-if="officeUser">
                            <ol class="mt-4">
                                <li class="pt-2">
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">POJECT PROCUREMENT MANAGEMENT PLAN (PPMP)</span>
                                    </p>
                                </li>
                                <li class="mb-1">
                                    <SidebarLink 
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('import.ppmp.index') || route().current('indiv.ppmp.show') }"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5v2H5v14h14v-5h2z"/>
                                            <path fill="currentColor" d="M21 7h-4V3h-2v4h-4v2h4v4h2V9h4z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">CREATE PPMP</span>
                                    </SidebarLink>
                                </li>
                                <li class="mb-1">
                                    <SidebarLink 
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('import.ppmp.index') || route().current('indiv.ppmp.show') }"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" d="M20 2a1 1 0 0 1 1 1v3.757l-8.999 9l-.006 4.238l4.246.006L21 15.242V21a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm1.778 6.808l1.414 1.414L15.414 18l-1.416-.002l.002-1.412zM12 12H7v2h5zm3-4H7v2h8z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">DRAFTED PPMP</span>
                                    </SidebarLink>
                                </li>
                                <li>
                                    <SidebarLink 
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('import.ppmp.index') || route().current('indiv.ppmp.show') }"
                                            fill="currentColor"
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" d="m17.275 20.25l3.475-3.45l-1.05-1.05l-2.425 2.375l-.975-.975l-1.05 1.075zM6 9h12V7H6zm12 14q-2.075 0-3.537-1.463T13 18t1.463-3.537T18 13t3.538 1.463T23 18t-1.463 3.538T18 23M3 22V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v6.675q-.7-.35-1.463-.513T18 11H6v2h7.1q-.425.425-.787.925T11.675 15H6v2h5.075q-.05.25-.062.488T11 18q0 1.05.288 2.013t.862 1.837L12 22l-1.5-1.5L9 22l-1.5-1.5L6 22l-1.5-1.5z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">APPROVED PPMP</span>
                                    </SidebarLink>
                                </li>
                            </ol>
                        </li>
                        <li>
                            <ol class="mt-4">
                                <li v-if="procurement" class="pt-2">
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">PROCUREMENT</span>
                                    </p>
                                </li>
                                <li class="mb-1">
                                    <SidebarDropdown
                                        v-if="hasPermission('view-purchase-request-list') ||  hasAnyRole(['Developer'])"
                                        :active="route().current('pr.display.procurementBasis') || route().current('pr.display.availableToPurchase') || route().current('pr.form.step1') || route().current('pr.form.step2') || route().current('pr.display.transactions') || route().current('pr.show.particular')">
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white" 
                                            :class="{'text-white': route().current('pr.display.procurementBasis') || route().current('pr.display.availableToPurchase') || route().current('pr.form.step1') || route().current('pr.form.step2') || route().current('pr.display.transactions') || route().current('pr.show.particular')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 576 512">
                                            <path fill="currentColor" d="M0 24C0 10.7 10.7 0 24 0h45.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5l-51.6-271c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24m128 440a48 48 0 1 1 96 0a48 48 0 1 1-96 0m336-48a48 48 0 1 1 0 96a48 48 0 1 1 0-96"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">PURCHASE REQUEST</span>
                                        <ArrowDown :class="{'text-white': route().current('pr.display.procurementBasis') || route().current('pr.display.availableToPurchase') || route().current('pr.form.step1') || route().current('pr.form.step2') || route().current('pr.display.transactions') || route().current('pr.show.particular')}" />
                                        <template #dropdown-items>
                                            <ul class="bg-gray-200 rounded-md">
                                                <li v-if="hasPermission('create-purchase-request') ||  hasAnyRole(['Developer'])">
                                                    <SubSidebarLink :href="route('pr.form.step1')" :active="route().current('pr.form.step1') || route().current('pr.form.step2')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('pr.form.step1') || route().current('pr.form.step2')}"/>
                                                        Create PR
                                                    </SubSidebarLink>
                                                </li>
                                                <li>
                                                    <SubSidebarLink :href="route('pr.display.transactions')" :active="route().current('pr.display.transactions') || route().current('pr.show.particular')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('/pr.display.transactions') || route().current('pr.show.particular')}"/>
                                                        PR Pending
                                                    </SubSidebarLink>
                                                </li>
                                                <li v-if="hasPermission('view-procurement-basis') ||  hasAnyRole(['Developer'])">
                                                    <SubSidebarLink :href="route('pr.display.procurementBasis')" :active="route().current('pr.display.procurementBasis') || route().current('pr.display.availableToPurchase')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('pr.display.procurementBasis') || route().current('pr.display.availableToPurchase')}"/>
                                                        PPMP-PR Overview
                                                    </SubSidebarLink>
                                                </li>
                                            </ul>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                                <li>
                                    <SidebarLink 
                                        v-if="hasPermission('view-purchase-order') ||  hasAnyRole(['Developer'])"
                                        :href="route('pr.show.onProcess')" :active="route().current('pr.show.onProcess')">
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('pr.show.onProcess') }"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24">
                                            <path fill="currentColor" d="m17.275 18.125l-.425-.425q-.225-.225-.537-.225t-.538.225t-.225.525t.225.525l.975.975q.225.225.525.225t.525-.225l2.425-2.375q.225-.225.225-.538t-.225-.537t-.538-.225t-.537.225zM17 9q.425 0 .713-.288T18 8t-.288-.712T17 7H7q-.425 0-.712.288T6 8t.288.713T7 9zm1 14q-2.075 0-3.537-1.463T13 18t1.463-3.537T18 13t3.538 1.463T23 18t-1.463 3.538T18 23M3 21.875V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v5.5q0 .425-.35.688t-.775.137q-.7-.175-1.425-.25T17 11H7q-.425 0-.712.288T6 12t.288.713T7 13h6.1q-.425.425-.787.925T11.675 15H7q-.425 0-.712.288T6 16t.288.713T7 17h4.075q-.05.25-.062.488T11 18q0 .65.125 1.275t.325 1.25q.125.275-.1.438t-.425-.038l-.075-.075q-.15-.15-.35-.15t-.35.15l-.8.8q-.15.15-.35.15t-.35-.15l-.8-.8q-.15-.15-.35-.15t-.35.15l-.8.8q-.15.15-.35.15t-.35-.15l-.8-.8q-.15-.15-.35-.15t-.35.15l-.8.8z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">PURCHASE ORDER</span>
                                    </SidebarLink>
                                </li>
                            </ol>
                        </li>
                        <li>
                            <ol class="mt-4">
                                <li v-if="inventory || officeUser" class="pt-2">
                                    <p class="flex flex-row items-center">
                                        <span class="text-sm font-bold tracking-wide text-gray-400">INVENTORY</span>
                                    </p>
                                </li>
                                <li class="mb-1">
                                    <SidebarDropdown 
                                        v-if="hasAnyRole(['Developer']) || hasPermission('view-iar-transaction-pending') || hasPermission('view-iar-transaction-all')"
                                        :active="$page.url.includes('/iar')" class="mb-1">
                                            <Inspect :class="{ 'text-white' : $page.url.includes('/iar')}"/>
                                            <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">ACCEPTANCE (IAR)</span>
                                            <ArrowDown :class="{'text-white': $page.url.includes('/iar')}" />
                                        <template #dropdown-items>
                                            <ul class="bg-gray-200 rounded-md">
                                                <li v-if="hasAnyRole(['Developer']) || hasPermission('view-iar-transaction-pending')">
                                                    <SubSidebarLink :href="route('iar')" :active="route().current('iar') || $page.url.includes('/iar/particulars')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('iar') || $page.url.includes('/iar/particulars')}"/>
                                                        Receiving
                                                    </SubSidebarLink>
                                                </li>
                                                <li v-if="hasAnyRole(['Developer']) || hasPermission('view-iar-transaction-all')">
                                                    <SubSidebarLink :href="route('show.iar.transactions')" :active="route().current('show.iar.transactions') || route().current('iar.particular.completed')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('iar.particular.completed')}"/>
                                                        All Transactions
                                                    </SubSidebarLink>
                                                </li>
                                            </ul>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                                <li class="mb-1">
                                    <SidebarDropdown
                                        v-if="hasAnyRole(['Developer']) || hasPermission('create-ris-transaction') || hasPermission('view-ris-transactions')"
                                        :active="route().current('create.ris') || route().current('ris.display.logs') || route().current('ris.show.items')" class="mb-1">
                                            <svg 
                                                class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                                :class="{ 'text-white' : route().current('create.ris') || route().current('ris.display.logs') || route().current('ris.show.items')}"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24">
                                                <path fill="currentColor" d="M9 11q-.85 0-1.425-.575T7 9V4q0-.825.575-1.412T9 2h6v9zm8 0V2h3q.825 0 1.413.588T22 4v5q0 .85-.587 1.425T20 11zM8 21v-8h6.8q.35 0 .6.2t.35.475t.038.575t-.338.525L13.975 16H10v1.5h4.5l4.05-3.35q.55-.4 1.163-.5t1.212.05t1.138.513t.912.937L17.1 20.075q-.55.45-1.2.688T14.55 21zm-5 1q-.425 0-.712-.288T2 21v-7q0-.425.288-.712T3 13h3v8q0 .425-.288.713T5 22z"/>
                                            </svg>
                                            <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">REQUISITION (RIS)</span>
                                            <ArrowDown :class="{'text-white': route().current('create.ris') || route().current('ris.display.logs') || route().current('ris.show.items')}" />
                                        <template #dropdown-items>
                                            <ul class="bg-gray-200 rounded-md">
                                                <li v-if="hasAnyRole(['Developer']) || hasPermission('create-ris-transaction')">
                                                    <SubSidebarLink :href="route('create.ris')" :active="route().current('create.ris') || route().current('ris.show.items')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('create.ris') || route().current('ris.show.items')}"/>
                                                        Releasing
                                                    </SubSidebarLink>
                                                </li>
                                                <li v-if="hasAnyRole(['Developer']) || hasPermission('view-ris-transactions')">
                                                    <SubSidebarLink :href="route('ris.display.logs')" :active="route().current('ris.display.logs')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('ris.display.logs')}"/>
                                                        Issuances
                                                    </SubSidebarLink>
                                                </li>
                                            </ul>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                                <li class="mb-1">
                                    <SidebarLink   
                                        v-if="hasAnyRole(['Developer']) || hasPermission('view-products-inventory')"
                                        :href="route('inventory.index')" :active="route().current('inventory.index')">
                                        <Stock :class="{ 'text-white' : route().current('inventory.index')}"/>
                                        <span class="ml-3 font-semibold">STOCK AVAILABLE</span>
                                    </SidebarLink>
                                </li>
                                <li class="mb-1">
                                    <SidebarLink 
                                        v-if="hasAnyRole(['Developer']) || hasPermission('monitor-expiring-products')"
                                        :href="route('show.expiry.products')" :active="route().current('show.expiry.products')" class="my-1">
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('show.expiry.products')}"
                                            aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M19 4h-2V2h-2v2H9V2H7v2H5c-1.103 0-2 .897-2 2v14c0 1.103.897 2 2 2h14c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2m-6 16h-2v-2h2zm0-4h-2v-5h2zm6-7H5V7h14z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">EXPIRING ITEMS</span>
                                    </SidebarLink>
                                </li>
                                <li v-if="officeUser">
                                    <SidebarLink 
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{'text-white': route().current('product.display.active')}"
                                            fill="currentColor" 
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path fill="currentColor" d="M5 21L3 9h18l-2 12zm4-6h6v-2H9zM5 8V6h14v2zm2-3V3h10v2z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">AVAILABLE QUANTITY</span>
                                    </SidebarLink>
                                    <SidebarLink 
                                        :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index') || route().current('indiv.ppmp.show')"
                                    >
                                        <svg 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('import.ppmp.index') || route().current('indiv.ppmp.show') }"
                                            fill="currentColor"
                                            aria-hidden="true" 
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 32 32"
                                        >
                                            <path fill="currentColor" d="M22 22v6H6V4h10V2H6a2 2 0 0 0-2 2v24a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6Z"/>
                                            <path fill="currentColor" d="m29.54 5.76l-3.3-3.3a1.6 1.6 0 0 0-2.24 0l-14 14V22h5.53l14-14a1.6 1.6 0 0 0 0-2.24ZM14.7 20H12v-2.7l9.44-9.45l2.71 2.71ZM25.56 9.15l-2.71-2.71l2.27-2.27l2.71 2.71Z"/>
                                        </svg>
                                        <span class="ml-3 font-semibold">PRODUCT REQUISTION</span>
                                    </SidebarLink>
                                </li>
                            </ol>
                        </li>
                        <li>
                            <ol class="mt-4">
                                <li>
                                    <div class="flex flex-row items-center">
                                        <div class="text-sm font-bold tracking-wide text-gray-400">OTHERS</div>
                                    </div>
                                </li>
                                <li class="mb-1"> 
                                    <SidebarDropdown :active="route().current('show.stockCard') || route().current('inventory.report') || route().current('view.ssmi')">
                                        <svg
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            :class="{ 'text-white' : route().current('show.stockCard') || route().current('inventory.report') || route().current('view.ssmi') }"
                                            fill="currentColor" 
                                            aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 897 1024"
                                        >
                                            <path fill="currentColor" d="M832.27 1024h-768q-26 0-45-18.5T.27 960V65q0-27 19-45.5t45-18.5h448v352q0 13 9.5 22.5t22.5 9.5h352v575q0 27-18.5 45.5t-45.5 18.5zm-96-192h-32V608q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v224h-64V480q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v352h-64V672q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v160h-32q-13 0-22.5 9.5t-9.5 22.5t9.5 22.5t22.5 9.5h576q14 0 23-9.5t9-22.5t-9.5-22.5t-22.5-9.5zm-160-832q26 0 44 18l257 257q19 19 19 46h-320V0z"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">GENERATE REPORT</span>
                                        <ArrowDown :class="{'text-white': route().current('show.stockCard') || route().current('inventory.report') || route().current('view.ssmi')}" />
                                        <template #dropdown-items>
                                            <ul class="bg-gray-200 rounded-md">
                                                <li>
                                                    <a :href="route('generatePdf.productInventoryList')" 
                                                        target="_blank" 
                                                        class="flex items-center p-2 mb-1 pl-5 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:text-gray-50 hover:bg-indigo-900">
                                                        <ArrowHeadRight />
                                                        <span class="ml-3">Beginning Balance</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <SubSidebarLink v-if="hasAnyRole(['Developer']) || hasPermission('view-product-stock-card')" :href="route('show.stockCard')" :active="route().current('show.stockCard')" class="my-1">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('ris.display.logs')}"/>
                                                        <span class="ml-3">Stock Card</span>
                                                    </SubSidebarLink>
                                                </li>
                                                <li>
                                                    <SubSidebarLink :href="route('inventory.report')" :active="route().current('inventory.report')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('inventory.report')}"/>
                                                        <span class="ml-3">Office and Janitorial Supplies Inventory</span>
                                                    </SubSidebarLink>
                                                </li>
                                                <li>
                                                    <SubSidebarLink :href="route('view.ssmi')" :active="route().current('view.ssmi')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('view.ssmi')}"/>
                                                        <span class="ml-3">Report of Supplies and Materials Issued</span>
                                                    </SubSidebarLink>
                                                </li>
                                            </ul>
                                        </template>
                                    </SidebarDropdown>
                                </li>
                                <li  v-if="hasAnyRole(['Developer', 'System Administrator'])">
                                    <SidebarDropdown :active="route().current('user') || route().current('user.roles') || route().current('user.permissions')" class="mb-1">
                                            <svg 
                                                class="w-7 h-7 text-indigo-900 transition duration-75 group-hover:text-white"
                                                :class="{ 'text-white' : route().current('user') || route().current('user.roles') || route().current('user.permissions')}"
                                                fill="currentColor"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="200"
                                                height="200"
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                            >
                                                <path fill="currentColor" fill-rule="evenodd" d="M17 10v1.1l1 .5l.8-.8l1.4 1.4l-.8.8l.5 1H21v2h-1.1l-.5 1l.8.8l-1.4 1.4l-.8-.8a4 4 0 0 1-1 .5V20h-2v-1.1a4 4 0 0 1-1-.5l-.8.8l-1.4-1.4l.8-.8a4 4 0 0 1-.5-1H11v-2h1.1l.5-1l-.8-.8l1.4-1.4l.8.8a4 4 0 0 1 1-.5V10zm.4 3.6c.4.4.6.8.6 1.4a2 2 0 0 1-3.4 1.4A2 2 0 0 1 16 13c.5 0 1 .2 1.4.6M5 8a4 4 0 1 1 8 .7a7 7 0 0 0-3.3 3.2A4 4 0 0 1 5 8m4.3 5H7a4 4 0 0 0-4 4v1c0 1.1.9 2 2 2h6.1a7 7 0 0 1-1.8-7" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">USERS SETTING</span>
                                            <ArrowDown :class="{'text-white': route().current('user') || route().current('user.roles') || route().current('user.permissions')}" />
                                        <template #dropdown-items>
                                            <ul class="bg-gray-200 rounded-md">
                                                <li v-if="hasAnyRole(['Developer'])">
                                                    <SubSidebarLink :href="route('user.roles')" :active="route().current('user.roles')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('user.roles')}"/>
                                                        Roles
                                                    </SubSidebarLink>
                                                </li>
                                                <li v-if="hasAnyRole(['Developer'])">
                                                    <SubSidebarLink :href="route('user.permissions')" :active="route().current('user.permissions')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('user.permissions')}"/>
                                                        Permissions
                                                    </SubSidebarLink>
                                                </li>
                                                <li>
                                                    <SubSidebarLink :href="route('user')" :active="route().current('user')">
                                                        <ArrowHeadRight :class="{ 'text-white' : route().current('user')}"/>
                                                        Users
                                                    </SubSidebarLink>
                                                </li>
                                            </ul> 
                                        </template>
                                    </SidebarDropdown>
                                </li>
                            </ol>
                        </li>
                        <li>
                            <ol class="mt-4">
                                <li>
                                    <div class="flex flex-row items-center">
                                        <div class="text-sm font-bold tracking-wide text-gray-400">DOWNLOADED FORMS</div>
                                    </div>
                                </li>
                                <li>
                                    <a 
                                        href="/WarePro.v2/public/assets/word_temps/office_ppmp_template.xlsx"
                                        class="flex items-center p-2 mb-1 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:text-gray-50 hover:bg-indigo-900"
                                        download>
                                        <svg 
                                            xmlns="http://www.w3.org/2000/svg" 
                                            class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                            fill="currentColor"
                                            aria-hidden="true"
                                            viewBox="0 0 512 512">
                                            <path fill="currentColor" d="M453.547 273.449H372.12v-40.714h81.427zm0 23.264H372.12v40.714h81.427zm0-191.934H372.12v40.713h81.427zm0 63.978H372.12v40.713h81.427zm0 191.934H372.12v40.714h81.427zm56.242 80.264c-2.326 12.098-16.867 12.388-26.58 12.796H302.326v52.345h-36.119L0 459.566V52.492L267.778 5.904h34.548v46.355h174.66c9.83.407 20.648-.291 29.197 5.583c5.991 8.608 5.41 19.543 5.817 29.43l-.233 302.791c-.29 16.925 1.57 34.2-1.978 50.892m-296.51-91.256c-16.052-32.57-32.395-64.909-48.39-97.48c15.82-31.698 31.408-63.512 46.937-95.327c-13.203.64-26.406 1.454-39.55 2.385c-9.83 23.904-21.288 47.169-28.965 71.888c-7.154-23.323-16.634-45.774-25.3-68.515c-12.796.698-25.592 1.454-38.387 2.21c13.493 29.78 27.86 59.15 40.946 89.104c-15.413 29.081-29.837 58.57-44.785 87.825c12.737.523 25.475 1.047 38.212 1.221c9.074-23.148 20.357-45.424 28.267-69.038c7.096 25.359 19.135 48.798 29.023 73.051c14.017.99 27.976 1.862 41.993 2.676M484.26 79.882H302.326v24.897h46.53v40.713h-46.53v23.265h46.53v40.713h-46.53v23.265h46.53v40.714h-46.53v23.264h46.53v40.714h-46.53v23.264h46.53v40.714h-46.53v26.897H484.26z"/>
                                        </svg>
                                        <span class="flex-1 ml-3 text-left whitespace-nowrap font-semibold">PPMP TEMPLATE</span>
                                    </a>
                                </li>
                            </ol>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
    </div>
</template>
