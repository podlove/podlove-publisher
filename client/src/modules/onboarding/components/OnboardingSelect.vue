<template>
    <div class="bg-white">
        <div class="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-black">
                    You can chose to either create a new podcast or import an existing podcast from 
                    another host. Choose the option that best suits your needs.
                </p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <PodloveButton variant="primary" @click="selectStart()">{{ __('Create new podcast') }}</PodloveButton>
                    <PodloveButton variant="primary" @click="selectImport()">{{ __('Import existing podcast') }}</PodloveButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import PodloveButton from '@components/button/Button.vue'
import { selectors } from '@store'
import * as adminStore from '@store/admin.store'

export default defineComponent({
    components: {
        PodloveButton
    },
    setup() {
        return {
            state: mapState({
                type: selectors.admin.type
            }),
            dispatch: injectStore().dispatch,
        }
    },
    created() {
        this.dispatch(adminStore.init())
    },
    methods: {
        selectStart() {
            this.dispatch(adminStore.update_type('start'))
        },
        selectImport() {
            this.dispatch(adminStore.update_type('import'))
        }
    }
})
</script>

<style lang="postcss">
</style>