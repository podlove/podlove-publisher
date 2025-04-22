<template>
  <div
    class="overflow-hidden rounded-lg bg-white border border-gray-200 p-5 transition-shadow duration-200 hover:shadow-sm"
  >
    <div class="px-4 py-5 sm:p-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-base font-medium text-gray-800">{{ title }}</h3>
        <div class="flex items-center gap-3">
          <span
            v-if="modelValue"
            class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-600"
            >Active</span
          >
          <span v-else class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500"
            >Disabled</span
          >
          <Switch
            :modelValue="modelValue"
            @update:modelValue="$emit('update:modelValue', $event)"
            :class="[
              modelValue ? 'bg-green-600' : 'bg-gray-200',
              'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2',
            ]"
          >
            <span
              :class="[
                modelValue ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
              ]"
            >
              <span
                :class="[
                  modelValue
                    ? 'opacity-0 duration-100 ease-out'
                    : 'opacity-100 duration-200 ease-in',
                  'absolute inset-0 flex size-full items-center justify-center transition-opacity',
                ]"
                aria-hidden="true"
              >
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                  <path
                    d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </span>
              <span
                :class="[
                  modelValue
                    ? 'opacity-100 duration-200 ease-in'
                    : 'opacity-0 duration-100 ease-out',
                  'absolute inset-0 flex size-full items-center justify-center transition-opacity',
                ]"
                aria-hidden="true"
              >
                <svg class="size-3 text-green-600" fill="currentColor" viewBox="0 0 12 12">
                  <path
                    d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z"
                  />
                </svg>
              </span>
            </span>
          </Switch>
        </div>
      </div>
      <slot></slot>
    </div>
    <div class="bg-gray-50 px-4 py-4 sm:px-6" v-if="$slots.footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { Switch } from '@headlessui/vue'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  modelValue: {
    type: Boolean,
    required: true,
  },
})

defineEmits(['update:modelValue'])
</script>
