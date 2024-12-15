<template>
    <ol role="list" class="divide-y divide-gray-300 rounded-md border border-gray-300 md:flex md:divide-y-0">
        <li v-for="(step, stepIdx) in steps" :key="step.name" class="relative md:flex md:flex-1">
            <p v-if="step.status === 'complete'" class="group flex w-full items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 group-hover:bg-indigo-800 text-white">
                        <CheckIcon class="h-6 w-6 text-white" aria-hidden="true" />
                    </span>
                    <span class="ml-4 text-sm font-medium text-gray-900">{{ step.name }}</span>
                </span>
            </p>
            <p v-else-if="step.status === 'current'" class="flex items-center px-6 py-4 text-sm font-medium"
                aria-current="step">
                <span
                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 border-indigo-600">
                    <span class="text-indigo-600">{{ step.id }}</span>
                </span>
                <span class="ml-4 text-sm font-medium text-indigo-600">{{ step.name }}</span>
            </p>
            <p v-else class="group flex items-center">
                <span class="flex items-center px-6 py-4 text-sm font-medium">
                    <span
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border-2 border-gray-300 group-hover:border-gray-400">
                        <span class="text-gray-500 group-hover:text-gray-900">{{ step.id }}</span>
                    </span>
                    <span class="ml-4 text-sm font-medium text-gray-500 group-hover:text-gray-900">{{ step.name
                        }}</span>
                </span>
            </p>
            <template v-if="stepIdx !== steps.length - 1">
                <!-- Arrow separator for lg screens and up -->
                <div class="absolute right-0 top-0 hidden h-full w-5 md:block" aria-hidden="true">
                    <svg class="h-full w-full text-gray-300" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                        <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </template>
        </li>
    </ol>
</template>

<script lang="ts">
import { CheckIcon } from '@heroicons/vue/24/solid'
import { defineComponent, PropType } from 'vue'

export type StepStatus = 'complete' | 'current' | 'upcoming'

export interface Step {
    id: Number,
    name: string,
    status: StepStatus
}

export default defineComponent({
    components: {
        CheckIcon,
    },
    props: {
        steps: {
            type: Array as PropType<Step[]>,
            default: [],
        }
    }
})

</script>