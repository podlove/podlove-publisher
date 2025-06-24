<template>
  <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
    <div class="mb-6">
      <h2 class="mb-2 text-xl font-medium text-gray-700">{{ __('Authentication', 'podlove-podcasting-plugin-for-wordpress') }}</h2>
      <p class="text-sm text-gray-600">
        {{ __('Publisher PLUS provides additional features and services for your podcast. Enter your API token below to activate these features.', 'podlove-podcasting-plugin-for-wordpress') }}
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="state.isLoading" class="space-y-3">
      <div class="animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="h-10 bg-gray-200 rounded mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Content when loaded -->
    <div v-else class="space-y-3">
      <TokenInput
        :modelValue="apiToken"
        :isTokenValid="isTokenValid"
        :isSaving="state.isSaving"
        @update:modelValue="handleTokenUpdate"
        @save="handleSaveToken"
      >
         <template #status>
          <div v-if="apiToken && isTokenValid" class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>{{ __('You are logged in as', 'podlove-podcasting-plugin-for-wordpress') }} <strong>{{ userEmail }}</strong></span>
          </div>
          <div v-else-if="apiToken && !isTokenValid" class="flex items-center gap-2 text-sm text-red-600">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>{{ __('Invalid API token', 'podlove-podcasting-plugin-for-wordpress') }}</span>
          </div>
          <div v-else class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>{{ __('No API token configured', 'podlove-podcasting-plugin-for-wordpress') }}</span>
          </div>
        </template>
      </TokenInput>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import TokenInput from './TokenInput.vue'
import { injectStore, mapState } from 'redux-vuex'
import * as plus from '@store/plus.store'
import { selectors } from '@store'

export default defineComponent({
  components: {
    TokenInput,
  },

  setup() {
    const store = injectStore()
    return {
      state: mapState({
        token: (state: any) => selectors.plus.token(state),
        user: (state: any) => selectors.plus.user(state),
        isLoading: (state: any) => selectors.plus.isLoading(state),
        isSaving: (state: any) => selectors.plus.isSaving(state),
      }),
      dispatch: store.dispatch,
    }
  },

  created() {
    this.dispatch(plus.init())
  },

  computed: {
    apiToken() {
      return this.state.token
    },
    isTokenValid() {
      return this.state.user !== null
    },
    userEmail() {
      return this.state.user?.email || ''
    },
    showContent() {
      return !this.state.isLoading
    },
  },

  methods: {
    handleTokenUpdate(token: string) {
      this.dispatch(plus.setToken(token))
    },
    handleSaveToken() {
      this.dispatch(plus.saveToken(this.apiToken))
    },
  },
})
</script>
