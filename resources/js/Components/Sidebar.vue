<script setup>
    import { ref} from 'vue';
    import SidebarLink from '@/Components/Buttons/SidebarLink.vue';
    import SidebarDropdown from '@/Components/Buttons/SidebarDropdown.vue';
    import SubSidebarLink from '@/Components/Buttons/SubSidebarLink.vue';
    import Humburger from '@/Components/Svgs/Hamburger.vue';
    import Pie from '@/Components/Svgs/Pie.vue';
    import ArrowDown from '@/Components/Svgs/ArrowDown.vue';
    import ArrowRight from '@/Components/Svgs/ArrowRight.vue';
    import Bag from '@/Components/Svgs/Bag.vue';
    import Inspect from '@/Components/Svgs/Inspect.vue';
    import Documents from '@/Components/Svgs/Documents.vue';
    import Files from '@/Components/Svgs/Files.vue';
    import CircleDot from '@/Components/Svgs/CircleDot.vue';
    import ClipboardList from '@/Components/Svgs/ClipboardList.vue';
    import ArrowHeadRight from './Svgs/ArrowHeadRight.vue';
    import Column from '@/Components/Svgs/Column.vue';
    import Add from '@/Components/Svgs/Add.vue';
    import Stock from './Svgs/Stock.vue';
    import Clipboard from './Svgs/Clipboard.vue';
</script>

<template>
    <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ml-3 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
        <span class="sr-only">Dashboard</span>
        <Humburger/>
    </button>

    <aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidenav">
        <div class="overflow-y-auto py-5 px-3 h-full  border-r border-gray-200 bg-gray-50">
            <ul class="space-y-2">
                <li class="px-5">
                    <div class="flex flex-row items-center">
                        <div class="text-sm font-light tracking-wide text-gray-500">Navigation</div>
                    </div>
                </li>
                <li>
                    <SidebarLink :href="route('dashboard')" :active="$page.component == 'Dashboard'">
                        <Pie :class="{'text-white': $page.component == 'Dashboard'}"/>
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
                            <Files :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active') || route().current('general.fund.display')}" />
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
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('category.display.all')}"/>
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
                    <SidebarDropdown :active="route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')">
                            <Column :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Products</span>
                            <ArrowDown :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}" />
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
                                    Price Timeline
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('product.unmodified.list')" :active="route().current('product.unmodified.list')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.unmodified.list')}"/>
                                    Unchanged Product (Qty)
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
                        <Add :class="{ 'text-white' : route().current('import.ppmp.index')}"/>
                        <span class="ml-3">Create</span>
                    </SidebarLink>
                </li>
                <li>
                    <SidebarDropdown :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'}) || route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})">
                            <ClipboardList :class="{'text-white': route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'}) || route().current('indiv.ppmp.type', { type: 'individual' , status: 'approved'})}" />
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
                            <Clipboard :class="{'text-white': route().current('conso.ppmp.type')}" />
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
                            <Bag :class="{'text-white': route().current('pr.display.procurementBasis') || route().current('pr.form.step1') || route().current('pr.display.transactions')}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Purchase Request</span>
                            <ArrowDown :class="{'text-white': route().current('pr.form.step1') || route().current('pr.display.transactions')}" />
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
                            <ArrowRight :class="{ 'text-white' : route().current('create.ris') || route().current('ris.display.logs')}"/>
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
                        <ArrowRight :class="{ 'text-white' : route().current('show.stockCard')}"/>
                        <span class="ml-3">Stock Card</span>
                    </SidebarLink>
                    <SidebarLink :href="route('show.expiry.products')" :active="route().current('show.expiry.products')" class="my-1">
                        <ArrowRight :class="{ 'text-white' : route().current('show.expiry.products')}"/>
                        <span class="ml-3">On Expiry</span>
                    </SidebarLink>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li> 
                    <SidebarLink :href="route('dashboard')" :active="false">
                        <Documents/>
                        <span class="ml-3">Reports</span>
                    </SidebarLink>
                </li>
                <li>
                    <SidebarLink :href="route('dashboard')" :active="false">
                        <CircleDot/>
                        <span class="ml-3">Frequently Question and Answer (FQA)</span>
                    </SidebarLink>
                </li>
            </ul>
        </div>
    </aside>
</template>
