<template>
    <div class="m-1 inline-flex">
        <span class="
            flex 
            items-center
            text-xs
            font-bold
            uppercase
            px-3
            py-1
            bg-indigo-200
            text-indigo-700
            rounded-full
        ">
            {{ value }}
            <button class="ml-1" @click="removeTag"><XCircleIcon class="w-5 h-5"/></button>
        </span>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { XCircleIcon } from "@heroicons/vue/solid"

export default defineComponent({
    name: 'PodloveTag',
    components: {
        XCircleIcon
    },
    props: {
        value: String,
        id: Number,
    },
    methods: {
        removeTag() {
            this.$emit('removeTag', this.id)
        }
    }
});
</script>

<style>
</style>