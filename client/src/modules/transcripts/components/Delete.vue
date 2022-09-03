<template>
  <div v-if="state.transcripts.length > 0">
    <podlove-button variant="secondary" size="small" @click="openModal()">Delete</podlove-button>
    <modal :open="modalVisible" @close="closeModal()">
      <div class="sm:flex sm:items-start">
        <div
          class="
            mx-auto
            flex-shrink-0 flex
            items-center
            justify-center
            h-12
            w-12
            rounded-full
            bg-red-100
            sm:mx-0 sm:h-10 sm:w-10
          "
        >
          <exclamation-icon class="h-6 w-6 text-red-600" aria-hidden="true" />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
            Delete Transcript
          </DialogTitle>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              {{ __('Are you sure you want to delete your transcript?') }}
            </p>
          </div>
        </div>
      </div>
      <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
        <podlove-button class="sm:ml-3 sm:w-auto sm:text-sm" variant="danger" @click="deleteTranscripts()">{{ __('Delete') }}</podlove-button>
        <podlove-button class="sm:ml-3 sm:w-auto sm:text-sm" @click="closeModal()">{{ __('Cancel') }}</podlove-button>
      </div>
    </modal>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { deleteTranscripts } from '@store/transcripts.store'
import { defineComponent } from '@vue/runtime-core'
import { ExclamationIcon } from '@heroicons/vue/outline'
import { DialogTitle } from '@headlessui/vue'

import selectors from '@store/selectors'
import PodloveButton from '@components/button/Button.vue'
import Modal from '@components/modal/Modal.vue'

export default defineComponent({
  components: {
    PodloveButton,
    Modal,
    ExclamationIcon,
    DialogTitle
  },

  data() {
    return {
      modalVisible: false,
    }
  },

  setup() {
    return {
      state: mapState({
        transcripts: selectors.transcripts.list,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    deleteTranscripts() {
      this.closeModal()
      this.dispatch(deleteTranscripts())
    },

    openModal() {
      this.modalVisible = true
    },

    closeModal() {
      this.modalVisible = false
    },
  },
})
</script>

<style>
</style>
