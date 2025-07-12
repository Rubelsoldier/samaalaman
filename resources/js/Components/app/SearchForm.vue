<template>
    <div class="w-full sm:w-[600px] h-[56px] sm:h-[80px] flex items-center px-2 sm:px-0">
        <TextInput type="text"
                   class="block w-full mr-2 bg-gray-200 border-slate-200 text-sm sm:text-base"
                   v-model="search"
                   autocomplete
                   @keyup.enter.prevent="onSearch"
                   placeholder="Search for files and folders.."
        />
    </div>
</template>

<script setup>
// Imports
import TextInput from "@/Components/TextInput.vue";
import {router, useForm} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import {emitter, ON_SEARCH} from "@/event-bus.js";

// Uses

let params = '' ;

// Refs
const search = ref('')

// Props & Emit

// Computed

// Methods
function onSearch() {
    params.set('search', search.value)
    router.get(window.location.pathname + '?' + params.toString())

    emitter.emit(ON_SEARCH, search.value)
}

// Hooks
onMounted(() => {
    params = new URLSearchParams(window.location.search)
    search.value = params.get('search')
})

</script>

<style scoped>

</style>
