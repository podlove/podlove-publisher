<template>
  <Listbox 
    :model-value="modelValue" 
    :multiple="multiple"
    @update:model-value="value => $emit('update:model-value', value)">
    <div class="relative mt-1">
      <ListboxButton
        class="relative py-2 pr-10 pl-3 w-full text-left bg-white rounded-lg shadow-md cursor-default focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm">
        <span v-if="label" class="block truncate">{{ label }}</span>
        <span v-else class="text-gray-500">{{ placeholder }}</span>
        <span class="flex absolute inset-y-0 right-0 items-center pr-2 pointer-events-none">
          <SelectorIcon aria-hidden="true" class="w-5 h-5 text-gray-400" />
        </span>
      </ListboxButton>
      <transition leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <ListboxOptions
          class="overflow-auto absolute z-10 py-1 mt-1 w-full max-h-60 text-base bg-white rounded-md ring-1 ring-black ring-opacity-5 shadow-lg focus:outline-none sm:text-sm">
          <ListboxOption 
            v-for="option in options" 
            :key="option.title" 
            v-slot="{ active, selected }"
            :value="option.id" 
            as="template"
          >
            <li :class="[
              active ? 'bg-amber-100 text-amber-900' : 'text-gray-900',
              'relative cursor-default select-none py-2 pl-10 pr-4',
            ]">
              <span :class="[
                selected ? 'font-medium' : 'font-normal',
                'block truncate',
              ]">{{ option.title }}</span>
              <span v-if="selected" class="flex absolute inset-y-0 left-0 items-center pl-3 text-amber-600">
                <CheckIcon aria-hidden="true" class="w-5 h-5" />
              </span>
            </li>
          </ListboxOption>
        </ListboxOptions>
      </transition>
      <div class="text-xs text-red-400 mt-1" v-if="error">{{ error }}</div>
    </div>
  </Listbox>
</template>

<script lang="ts">
import { defineComponent, PropType, toRaw } from '@vue/runtime-core'
import {
  Listbox,
  ListboxButton,
  ListboxOption,
  ListboxOptions
} from '@headlessui/vue'
import {CheckIcon, SelectorIcon} from "@heroicons/vue/solid"

export interface OptionObject {
  id: number
  title: string
}

export default defineComponent({
  components: {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    CheckIcon,
    SelectorIcon,
  },

  props: {
    options: Array as PropType<OptionObject[]>,
    modelValue: Array,
    placeholder: {
      type: String,
      default: 'Select an option',
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    error: String,
  },

  emits: ["update:model-value"],

  computed: {
    label() {
      return this.options?.filter(option  => {
        if (Array.isArray(this.modelValue)) {
          return this.modelValue.includes(option.id)
        }
        return this.modelValue === option.id;
      })
        .map(option => option.title)
        .join(', ');
    }
  }
})
</script>

<style>
</style>
