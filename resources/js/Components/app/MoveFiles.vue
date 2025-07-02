<template>
    <!-- <pre>
_lft: {{ props.selectedFile?._lft }}
_rgt: {{ props.selectedFile?._rgt }}
width: {{ width }}
    </pre> -->
    <!-- <pre>width is {{ width }}</pre> -->
    <button @click="onClick"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white mr-3">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
        </svg>
        Move Files
        <!-- <pre>{{ ancestors }}</pre> -->
    </button>

    <MoveFilesModal 
        v-model="showEmailsModal"                 
        :ancestors="ancestors" 
        :width="width"
        :selectedFile_lft="selectedFile_lft"
        :selectedFile_rgt="selectedFile_rgt"
    />
</template>

<script setup>
// Imports
import {ref, computed} from "vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";
import MoveFilesModal from "@/Components/app/MoveFilesModal.vue";

// Props & Emit

const props = defineProps({
    allSelected: {
        type: Boolean,
        required: false,
        default: false
    },
    selectedIds: {
        type: Array,
        required: true
    },
    ancestors: {
        type: Object,
        required: true
    },
    selectedFile: {
        type: Object,
        required: false,
        default: () => ({})
    }
})
const emit = defineEmits(['restore'])

// Refs
const showEmailsModal = ref(false);

// Make width reactive to selectedFile changes
const width = computed(() =>
    props.selectedFile && Number.isInteger(props.selectedFile._lft) && Number.isInteger(props.selectedFile._rgt)
        ? props.selectedFile._rgt - props.selectedFile._lft + 1
        : 0
);

const selectedFile_lft = computed(() => props.selectedFile?._lft ?? null);
const selectedFile_rgt = computed(() => props.selectedFile?._rgt ?? null);

// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null
})

// Methods

function onClick() {
    if (!props.allSelected && !props.selectedIds.length) {
        showErrorDialog('Please select at least one file to move')
        return
    }
    showEmailsModal.value = true;
}


// Hooks

</script>

<style scoped>

</style>
