<template>
  <Combobox
    :model-value="selectValues" 
    :multiple="multiple"
    @update:model-value="selectItem($event)"
    >
    <div class="
      relative
      mt-1
      focus-visible:border-indigo-500
      focus:ring-indigo-500
      ">
      <ComboboxButton
        class="
          w-full
          flex 
          items-center 
        "
        @click="resetQuery"
      >
      <ComboboxInput
        @change="onChange"
        class="
          relative
          py-2
          pl-3
          w-full
          bg-white
          rounded-lg
          shadow-md
          cursor-default
          sm:text-sm
          border-gray-300
        "
      >
      </ComboboxInput>
      <div class="
          shadow-sm
          flex
          sm:text-sm
          rounded-md
          absolute
          inset-y-0
          right-10
          items-center
          pr-2
          pointer-events-none
        "
        >
        {{ label }}
      </div>
        <SelectorIcon 
          class="
            w-5 
            h-5 
            text-gray-400
          " 
          aria-hidden="true"
        />
      </ComboboxButton>
      <transition 
        leave-active-class="
          transition 
          duration-100 
          ease-in
        " 
        leave-from-class="
          opacity-100
        "
        leave-to-class="
          opacity-0
        "
      >
        <ComboboxOptions
          class="
            -top-2 transform -translate-y-full
            overflow-auto 
            absolute 
            z-10 
            py-1 
            mt-1 
            w-full 
            max-h-60 
            text-base 
            bg-white 
            rounded-md 
            ring-1 
            ring-black 
            ring-opacity-5 
            shadow-lg 
            focus:outline-none 
            sm:text-sm
          "
        >
          <div
            v-if="filterOptions?.length === 0 && query !== ''"
            class="
              cursor-default
              select-none
              relative
              py-2
              px-4
              text-gray-700
            "
          >
            Nothing found.
          </div>
          <ComboboxOption 
            v-for="option in filterOptions" 
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
          </ComboboxOption>
        </ComboboxOptions>
      </transition>
      <div class="text-xs text-red-400 mt-1" v-if="error">{{ error }}</div>
    </div>
  </Combobox>
</template>

<script lang="ts">
import { defineComponent, PropType, toRaw } from '@vue/runtime-core'
import {
  Combobox,
  ComboboxButton,
  ComboboxInput,
  ComboboxOption,
  ComboboxOptions
} from '@headlessui/vue'
import {CheckIcon, ChevronUpDownIcon as SelectorIcon} from "@heroicons/vue/24/solid"

export interface OptionObject {
  id: number
  title: string
}

export default defineComponent({
  name: "PodloveCombobox",
  components: {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
    CheckIcon,
    SelectorIcon,
  },

  props: {
    options: Array as PropType<OptionObject[]>,
    selectValues: Array as PropType<Number[]>,
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

  computed: {
    label() : string | undefined {
      const numOfSelect = this.selectValues?.length
      if (numOfSelect === 0)
        return "No option is selected"
      else if (numOfSelect === 1)
        return "One option is selected"
      else 
        return numOfSelect?.toString() + " options are selected"
    },

    filterOptions() : Array<OptionObject> | undefined {
      if (this.query === '')
        return this.options
      return this.options?.filter(option => {
        return option.title.toLowerCase().includes(this.query.toLowerCase())
      })
    }
  },

  data() {
    return {
      query: ''
    }
  },

  methods: {
    selectItem(newSelectedItems: Array<Number>) {
      this.$emit('update', newSelectedItems)
    },
    onChange(event: Event) {
      this.query = (event.target as HTMLInputElement).value
    },
    resetQuery() {
      this.query = ''
    }
  }
})
</script>

<style>
</style>
