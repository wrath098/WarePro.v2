<script setup>
    import SidebarLink from '@/Components/Buttons/SidebarLink.vue';
    import SidebarDropdown from '@/Components/Buttons/SidebarDropdown.vue';
    import SubSidebarLink from '@/Components/Buttons/SubSidebarLink.vue';
    import ArrowDown from '@/Components/Svgs/ArrowDown.vue';
    import Inspect from '@/Components/Svgs/Inspect.vue';
    import ArrowHeadRight from '@/Components/Svgs/ArrowHeadRight.vue';
    import Stock from '@/Components/Svgs/Stock.vue';
</script>

<template>
    <div class="lg:block">
        <aside class="fixed w-80 h-[calc(100vh-8rem)] z-50 overflow-y-auto select-none top-24 lg:left-8 transition-all rounded-lg p-2 bg-white">
            <nav class="text-sm text-gray-700 mb-5">
                <div class="overflow-y-auto py-3 px-3 h-full">
                    <ul class="space-y-2">
                        <li class="px-5">
                            <div class="flex flex-row items-center">
                                <div class="text-sm font-light tracking-wide text-gray-500">Navigation</div>
                            </div>
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
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Dashboard</span>
                            </SidebarLink>
                        </li>
                        <li>
                            <div class="px-5 pt-2">
                                <div class="flex flex-row items-center">
                                    <div class="text-sm font-light tracking-wide text-gray-500">Core</div>
                                </div>
                            </div>
                            <SidebarDropdown :active="route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display')">
                                    <svg 
                                        class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                        :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display')}"
                                        fill="currentColor" 
                                        aria-hidden="true" 
                                        viewBox="0 0 20 20" 
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
                                    </svg>
                                    <span class="flex-1 ml-3 text-left whitespace-nowrap">Components</span>
                                    <ArrowDown :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display')}" />
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('fund.display.all')" :active="route().current('fund.display.all')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('fund.display.all')}"/>
                                            Account Classification
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('category.display.active')" :active="route().current('category.display.active')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('category.display.active')}"/>
                                            Categories
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('item.display.active')" :active="route().current('item.display.active')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('item.display.active')}"/>
                                            Item Classes
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('office.display.active')" :active="route().current('office.display.active')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('office.display.active')}"/>
                                            Offices
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('general.fund.display')" :active="route().current('general.fund.display')">
                                            <ArrowHeadRight :class="{ 'text-white ': route().current('general.fund.display')}"/>
                                            Annual Budget 
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                        </li>
                        <li>
                            <SidebarDropdown :active="$page.url.includes('/product')">
                                <svg
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{'text-white': $page.url.includes('/product')}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 24 24"
                                >
                                    <path fill="currentColor" fill-rule="evenodd" d="M15 4H9v16h6zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3zM4 4h3v16H4a2 2 0 0 1-2-2V6c0-1.1.9-2 2-2" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Products</span>
                                <ArrowDown :class="{'text-white': $page.url.includes('/product')}" />
                                
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('product.display.active')" :active="route().current('product.display.active')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active')}"/>
                                            Item List
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('product.display.active.pricelist')" :active="route().current('product.display.active.pricelist')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active.pricelist')}"/>
                                            Price List
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('product.unmodified.list')" :active="route().current('product.unmodified.list')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('product.unmodified.list')}"/>
                                            Fixed Quantity
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                        </li>
                        <li>
                            <div class="px-5 pt-2">
                                <div class="flex flex-row items-center">
                                    <div class="text-sm font-light tracking-wide text-gray-500">PPMP</div>
                                </div>
                            </div>
                            <SidebarLink :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index')">
                                <svg 
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{ 'text-white' : route().current('import.ppmp.index')}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                >
                                    <path fill="currentColor" d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5v2H5v14h14v-5h2z"/>
                                    <path fill="currentColor" d="M21 7h-4V3h-2v4h-4v2h4v4h2V9h4z"/>
                                </svg>
                                <span class="ml-3">Create</span>
                            </SidebarLink>
                        </li>
                        <li>
                            <SidebarDropdown :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'}) || route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})">
                                <svg 
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{'text-white': route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'}) || route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16"
                                >
                                    <path fill="currentColor" fill-rule="evenodd" d="M2.9 1L5 3.1l-.8.7L3 2.6V7H2V2.5L.8 3.8l-.7-.7L2.2 1h.7zM3 13.4V9H2v4.4L.8 12.2l-.7.7L2.2 15h.7L5 12.9l-.7-.7L3 13.4zM8.5 7h-2L6 6.5v-2l.5-.5h2l.5.5v2l-.5.5zM7 6h1V5H7v1zm7.5 1h-3l-.5-.5v-3l.5-.5h3l.5.5v3l-.5.5zM12 6h2V4h-2v2zm-3.5 6h-2l-.5-.5v-2l.5-.5h2l.5.5v2l-.5.5zM7 11h1v-1H7v1zm7.5 2h-3l-.5-.5v-3l.5-.5h3l.5.5v3l-.5.5zM12 12h2v-2h-2v2zm-1-2H9v1h2v-1zm0-5H9v1h2V5z" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Individual</span>
                                <ArrowDown :class="{'text-white': route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'}) || route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})}" />
                                
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('indiv.ppmp.type', { type: 'individual' , status: 'draft'})" :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'})">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'})}"/>
                                            Draft
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('indiv.ppmp.type', { type: 'individual' , status: 'approved'})" :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})}"/>
                                            Approved
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                        </li>
                        <li>
                            <SidebarDropdown :active="route().current('conso.ppmp.type')">
                                <svg 
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{'text-white': route().current('conso.ppmp.type')}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16"
                                >
                                    <path fill="currentColor" fill-rule="evenodd" d="M1.5 1h2v1H2v12h1.5v1h-2l-.5-.5v-13l.5-.5zm6 6h-2L5 6.5v-2l.5-.5h2l.5.5v2l-.5.5zM6 6h1V5H6v1zm7.5 1h-3l-.5-.5v-3l.5-.5h3l.5.5v3l-.5.5zM11 6h2V4h-2v2zm-3.5 6h-2l-.5-.5v-2l.5-.5h2l.5.5v2l-.5.5zM6 11h1v-1H6v1zm7.5 2h-3l-.5-.5v-3l.5-.5h3l.5.5v3l-.5.5zM11 12h2v-2h-2v2zm-1-2H8v1h2v-1zm0-5H8v1h2V5z" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Consolidated</span>
                                <ArrowDown :class="{'text-white': route().current('conso.ppmp.type')}" />

                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" :active="route().current('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})}"/>
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
                        <li>
                            <div class="px-5 pt-2">
                                <div class="flex flex-row items-center">
                                    <div class="text-sm font-light tracking-wide text-gray-500">Procurement</div>
                                </div>
                            </div>
                            <SidebarDropdown :active="route().current('pr.display.procurementBasis') || route().current('pr.form.step1') || route().current('pr.display.transactions')">

                                <svg
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{'text-white': route().current('pr.display.procurementBasis') || route().current('pr.form.step1') || route().current('pr.display.transactions')}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 24 24"
                                >
                                    <path fill="currentColor" fill-rule="evenodd" d="M10 2.25a1.75 1.75 0 0 0-1.582 1c-.684.006-1.216.037-1.692.223A3.25 3.25 0 0 0 5.3 4.563c-.367.493-.54 1.127-.776 1.998l-.047.17l-.513 2.964c-.185.128-.346.28-.486.459c-.901 1.153-.472 2.87.386 6.301c.545 2.183.818 3.274 1.632 3.91C6.31 21 7.435 21 9.685 21h4.63c2.25 0 3.375 0 4.189-.635c.814-.636 1.086-1.727 1.632-3.91c.858-3.432 1.287-5.147.386-6.301a2.186 2.186 0 0 0-.487-.46l-.513-2.962l-.046-.17c-.237-.872-.41-1.506-.776-2a3.25 3.25 0 0 0-1.426-1.089c-.476-.186-1.009-.217-1.692-.222A1.75 1.75 0 0 0 14 2.25h-4Zm8.418 6.896l-.362-2.088c-.283-1.04-.386-1.367-.56-1.601a1.75 1.75 0 0 0-.768-.587c-.22-.086-.486-.111-1.148-.118A1.75 1.75 0 0 1 14 5.75h-4a1.75 1.75 0 0 1-1.58-.998c-.663.007-.928.032-1.148.118a1.75 1.75 0 0 0-.768.587c-.174.234-.277.56-.56 1.6l-.362 2.089C6.58 9 7.91 9 9.685 9h4.63c1.775 0 3.105 0 4.103.146ZM8 12.25a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4a.75.75 0 0 1 .75-.75Zm8.75.75a.75.75 0 0 0-1.5 0v4a.75.75 0 0 0 1.5 0v-4ZM12 12.25a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">Purchase Request</span>
                                <ArrowDown :class="{'text-white': route().current('pr.display.procurementBasis') || route().current('pr.form.step1') || route().current('pr.display.transactions')}" />
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('pr.display.procurementBasis')" :active="route().current('pr.display.procurementBasis')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('pr.display.procurementBasis')}"/>
                                            Procurement Basis
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('pr.form.step1')" :active="route().current('pr.form.step1')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('pr.form.step1')}"/>
                                            Create
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('pr.display.transactions')" :active="route().current('pr.display.transactions')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('pr.display.transactions')}"/>
                                            Pending Approval
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="''" :active="false">
                                            <ArrowHeadRight :class="false"/>
                                            In Progress
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                        </li>
                        
                        <li>
                            <div class="px-5 pt-2">
                                <div class="flex flex-row items-center">
                                    <div class="text-sm font-light tracking-wide text-gray-500">Inventory</div>
                                </div>
                            </div>
                            <SidebarDropdown :active="route().current('iar') || route().current('show.iar.transactions')" class="mb-1">
                                    <Inspect :class="{ 'text-white' : route().current('iar') || route().current('show.iar.transactions')}"/>
                                    <span class="flex-1 ml-3 text-left whitespace-nowrap">Inspection and Acceptance</span>
                                    <ArrowDown :class="{'text-white': route().current('iar') || route().current('show.iar.transactions')}" />
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('iar')" :active="route().current('iar')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('iar')}"/>
                                            Pending Status
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('show.iar.transactions')" :active="route().current('show.iar.transactions')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('show.iar.transactions')}"/>
                                            All Transactions
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                            <SidebarDropdown :active="route().current('create.ris') || route().current('ris.display.logs')" class="mb-1">
                                    <svg
                                        class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                        :class="{ 'text-white' : route().current('create.ris') || route().current('ris.display.logs')}"
                                        fill="currentColor" 
                                        aria-hidden="true" 
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 2048 2048"
                                    >
                                        <path fill="currentColor" d="M896 1537V936L256 616v880l544 273l-31 127l-641-320V472L960 57l832 415v270q-70 11-128 45V616l-640 320v473l-128 128zM754 302l584 334l247-124l-625-313l-206 103zm206 523l240-120l-584-334l-281 141l625 313zm888 71q42 0 78 15t64 41t42 63t16 79q0 39-15 76t-43 65l-717 717l-377 94l94-377l717-716q29-29 65-43t76-14zm51 249q21-21 21-51q0-31-20-50t-52-20q-14 0-27 4t-23 15l-692 692l-34 135l135-34l692-691z"/>
                                    </svg>
                                    <span class="flex-1 ml-3 text-left whitespace-nowrap">Requisition and Issuance</span>
                                    <ArrowDown :class="{'text-white': route().current('create.ris') || route().current('ris.display.logs')}" />
                                <template #dropdown-items>
                                    <li>
                                        <SubSidebarLink :href="route('create.ris')" :active="route().current('create.ris')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('create.ris')}"/>
                                            Create New
                                        </SubSidebarLink>
                                    </li>
                                    <li>
                                        <SubSidebarLink :href="route('ris.display.logs')" :active="route().current('ris.display.logs')">
                                            <ArrowHeadRight :class="{ 'text-white' : route().current('ris.display.logs')}"/>
                                            Issuances
                                        </SubSidebarLink>
                                    </li>
                                </template>
                            </SidebarDropdown>
                            <SidebarLink :href="route('inventory.index')" :active="route().current('inventory.index')">
                                <Stock :class="{ 'text-white' : route().current('inventory.index')}"/>
                                <span class="ml-3">Current Stock</span>
                            </SidebarLink>
                            <SidebarLink :href="route('show.stockCard')" :active="route().current('show.stockCard')" class="my-1">
                                <svg 
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{ 'text-white' : route().current('show.stockCard')}"
                                    fill="currentColor" 
                                    aria-hidden="true" 
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16"
                                >
                                    <path fill="currentColor" d="M12 6V0H4v6H0v7h16V6h-4zm-5 6H1V7h2v1h2V7h2v5zM5 6V1h2v1h2V1h2v5H5zm10 6H9V7h2v1h2V7h2v5zM0 16h3v-1h10v1h3v-2H0v2z"/>
                                </svg>
                                <span class="ml-3">Stock Card</span>
                            </SidebarLink>
                            <SidebarLink :href="route('show.expiry.products')" :active="route().current('show.expiry.products')" class="my-1">
                                <svg 
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    :class="{ 'text-white' : route().current('show.expiry.products')}"
                                    fill="currentColor" 
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16"
                                >
                                    <path fill="currentColor" fill-rule="evenodd" d="M8.175.002a8 8 0 1 0 2.309 15.603a.75.75 0 0 0-.466-1.426a6.5 6.5 0 1 1 3.996-8.646a.75.75 0 0 0 1.388-.569A8 8 0 0 0 8.175.002ZM8.75 3.75a.75.75 0 0 0-1.5 0v3.94L5.216 9.723a.75.75 0 1 0 1.06 1.06L8.53 8.53l.22-.22V3.75ZM15 15a1 1 0 1 1-2 0a1 1 0 0 1 2 0Zm-.25-6.25a.75.75 0 0 0-1.5 0v3.5a.75.75 0 0 0 1.5 0v-3.5Z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3">On Expiry</span>
                            </SidebarLink>
                        </li>
                    </ul>
                    <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                        <!-- <li> 
                            <SidebarLink :href="''" :active="false">
                                <svg
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    fill="currentColor" 
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 897 1024"
                                >
                                    <path fill="currentColor" d="M832.27 1024h-768q-26 0-45-18.5T.27 960V65q0-27 19-45.5t45-18.5h448v352q0 13 9.5 22.5t22.5 9.5h352v575q0 27-18.5 45.5t-45.5 18.5zm-96-192h-32V608q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v224h-64V480q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v352h-64V672q0-13-9.5-22.5t-22.5-9.5h-64q-13 0-22.5 9.5t-9.5 22.5v160h-32q-13 0-22.5 9.5t-9.5 22.5t9.5 22.5t22.5 9.5h576q14 0 23-9.5t9-22.5t-9.5-22.5t-22.5-9.5zm-160-832q26 0 44 18l257 257q19 19 19 46h-320V0z"/>
                                </svg>
                                <span class="ml-3">Reports</span>
                            </SidebarLink>
                        </li> -->
                        <li>
                            <SidebarLink :href="route('dashboard')" :active="false">
                                <svg
                                    class="w-6 h-6 text-indigo-900 transition duration-75 group-hover:text-white"
                                    fill="currentColor" 
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                >
                                    <path fill="currentColor" d="M8 18h10.237L20 19.385V9h1a1 1 0 0 1 1 1v13.5L17.546 20H9a1 1 0 0 1-1-1v-1Zm-2.545-2L1 19.5V4a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v12H5.455Z"/>
                                </svg>
                                <span class="ml-3">Frequently Question and Answer (FQA)</span>
                            </SidebarLink>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
    </div>
</template>
