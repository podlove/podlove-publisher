<template>
    <module name="onboarding" :title="__('Onboarding')">
        <div class="px-4 pt-4 w-full">
            <div v-if="state.type === null">
                <OnboardingSelect></OnboardingSelect>
            </div>
            <div v-if="state.type === 'start'">
                <OnboardingStart></OnboardingStart>
            </div>
            <div v-if="state.type === 'import'">
                <OnboardingImport></OnboardingImport>
            </div>
        </div>
    </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import Module from '@components/module/Module.vue'
import OnboardingSelect from './components/OnboardingSelect.vue'
import OnboardingImport from './components/OnboardingImport.vue'
import OnboardingStart from './components/OnboardingStart.vue'
import { selectors } from '@store'
import * as adminStore from '@store/admin.store'

export default defineComponent({
    components: {
        Module,
        OnboardingSelect,
        OnboardingStart,
        OnboardingImport
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
})
</script>

<style lang="postcss">
</style>