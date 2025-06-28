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
            <CheckCircleIcon class="w-4 h-4 text-green-600" />
            <span>{{ __('You are logged in as', 'podlove-podcasting-plugin-for-wordpress') }} <strong>{{ userEmail }}</strong></span>
          </div>
          <div v-else-if="apiToken && !isTokenValid" class="flex items-center gap-2 text-sm text-red-600">
            <XCircleIcon class="w-4 h-4" />
            <span>{{ __('Invalid API token', 'podlove-podcasting-plugin-for-wordpress') }}</span>
          </div>
          <div v-else class="flex items-center gap-2 text-sm text-gray-600">
            <InformationCircleIcon class="w-4 h-4" />
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
import { CheckCircleIcon, XCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/solid'
import { injectStore, mapState } from 'redux-vuex'
import * as plus from '@store/plus.store'
import { selectors } from '@store'

export default defineComponent({
  components: {
    TokenInput,
    CheckCircleIcon,
    XCircleIcon,
    InformationCircleIcon,
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
