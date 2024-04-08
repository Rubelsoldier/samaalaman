<template>
    <nav class="min-w-[200px] bg-slate-300">
        <div>
            <Link href="/" class="h-[80px] px-3 flex items-center gap-3">
                <ApplicationLogo
                class="block h-9 w-auto fill-current text-gray-800"
                />
            SamaAlAman
            </Link>
        </div>
        <div class="px-3">
            <CreateNewDropdown />
            <div class="py-3">
                <NavLink href="/" :active="true">My Files</NavLink>
                <NavLink href="/">Shared with me</NavLink>
                <NavLink href="/">Shared by me</NavLink>
                <NavLink href="/">Trash</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import ApplicationLogo from '../ApplicationLogo.vue';
import {Link} from '@inertiajs/vue3'
import CreateNewDropdown from '@/Components/app/CreateNewDropdown.vue';
import NavLink from '@/Components/NavLink.vue';

//Imports


//Uses


//Refs


//Props and Emit


//Computed


//Methods


//Hooks


</script>
