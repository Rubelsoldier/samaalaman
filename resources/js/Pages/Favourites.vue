<template>
    <AuthenticatedLayout>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected" />
                    </th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left"></th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Path
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="file of allFiles.data" :key="file.id"
                    @click="$event => toggleFileSelect(file) "
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="(selected[file.id] || allSelected ) ? 'bg-blue-50' : 'bg-white'">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="$event => onSelectCheckboxChange(file)" v-model="selected[file.id]" :checked="selected[file.id] || allSelected" />
                    </td>
                    <td class="px-6 py-2 sm:py-4 max-w-[40px] font-medium text-gray-900 text-yellow-500">
                        <div @click.stop.prevent="addRemoveFavourite(file)">
                            <svg v-if="!file.is_favourite" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-5 h-5 sm:w-6 sm:h-6">
                                <path fill-rule="evenodd"
                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
                                        clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                        <FileIcon :file="file"/>
                        {{ file.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.path }}
                    </td>
                </tr>
                </tbody>
            </table>

            <div v-if="!allFiles.data.length" class="py-8 text-center text-sm text-gray-400">
                There is no data in this folder
            </div>
            <div ref="loadMoreIntersect"></div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
// Imports
import {HomeIcon} from '@heroicons/vue/20/solid'
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router} from "@inertiajs/vue3";
import {Link} from '@inertiajs/vue3'
import FileIcon from "@/Components/app/FileIcon.vue";
import {computed, onMounted, onUpdated, ref} from "vue";
import {httpGet,httpPost} from "@/Helper/http-helper.js";
import Checkbox from "@/Components/Checkbox.vue";
import DeleteFilesButton from "@/Components/app/DeleteFilesButton.vue";
import DownloadFilesButton from "@/Components/app/DownloadFilesButton.vue";
import RestoreFilesButton from "@/Components/app/RestoreFilesButton.vue";
import DeleteForeverButton from "@/Components/app/DeleteForeverButton.vue";

// Uses

// Refs
const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null)

// Props & Emit
const props = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object
})

const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next
})

// Computed
const selectedIds = computed(() => Object.entries(selected.value).filter(a => a[1]).map(a => a[0]))

// Methods
function loadMore() {
    console.log("load more");
    console.log(allFiles.value.next);

    if (allFiles.value.next === null) {
        return
    }

    httpGet(allFiles.value.next)
        .then(res => {
            allFiles.value.data = [...allFiles.value.data, ...res.data]
            allFiles.value.next = res.links.next
        })
}

function onSelectAllChange(){
    allFiles.value.data.forEach(f => {
        selected.value[f.id] = allSelected.value
    })
}

function toggleFileSelect(file){
    selected.value[file.id] = !selected.value[file.id]
    onSelectCheckboxChange(file)
}

function onSelectCheckboxChange(file){
    if (!selected.value[file.id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        for (let file of allFiles.value.data) {
            if (!selected.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked

    }
}

function addRemoveFavourite(file) {

httpPost(route('file.addToFavourites'), {id: file.id})
    .then(() => {
        file.is_favourite = !file.is_favourite
        showSuccessNotification('Selected files have been added to favourites')
    })
    .catch(async (er) => {
        console.log(er.error.message);
    })
}

function resetForm(){
    allSelected.value = false
    selected.value = {}
}

// Hooks
onUpdated(() => {
    allFiles.value = {
        data: props.files.data,
        next: props.files.links.next
    }
})

onMounted(() => {
    const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
        rootMargin: '-250px 0px 0px 0px'
    })

    observer.observe(loadMoreIntersect.value)
})


</script>

<style scoped>

</style>
