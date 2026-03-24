<template>
  <div class="space-y-4">
    <div>
      <label for="api_token" class="block text-sm font-medium text-gray-700 mb-2">
        {{ __('API Token', 'podlove-podcasting-plugin-for-wordpress') }}
      </label>
      <div class="relative">
        <input
          :type="showToken ? 'text' : 'password'"
          id="api_token"
          :value="modelValue"
          @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
          class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          :placeholder="__('Enter your API token', 'podlove-podcasting-plugin-for-wordpress')"
        />
        <button
          type="button"
          @click="showToken = !showToken"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
        >
          <EyeIcon v-if="showToken" class="h-5 w-5" />
          <EyeSlashIcon v-else class="h-5 w-5" />
        </button>
      </div>
    </div>

    <div class="text-sm text-gray-600">
      <slot></slot>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex-1">
        <slot name="status"></slot>
      </div>

      <div class="flex gap-2">
        <a
          href="https://plus.podlove.org/tokens"
          target="_blank"
          class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          {{ __('Get Token', 'podlove-podcasting-plugin-for-wordpress') }}
        </a>
        <button
          type="button"
          :disabled="isSaving"
          @click="$emit('save')"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isSaving ? __('Saving...', 'podlove-podcasting-plugin-for-wordpress') : __('Save Token', 'podlove-podcasting-plugin-for-wordpress') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  isTokenValid: {
    type: Boolean,
    default: false,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue', 'save'])

const showToken = ref(false)
</script>
