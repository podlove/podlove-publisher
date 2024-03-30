<template>
    <module name="soundbite" :title="__('Soundbite', 'podlove-podcasting-plugin-for-wordpress')">
        <template v-slot:actions>
            <soundbite-clear></soundbite-clear>
        </template>
        <soundbite-form></soundbite-form>
    </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

import Module from '@components/module/Module.vue'
import * as episode from '@store/episode.store'

import { injectStore } from 'redux-vuex'

import SoundbiteClear from './components/Clear.vue'
import SoundbiteForm from './components/Form.vue'

export default defineComponent({
    components: {
        Module,
        SoundbiteForm,
        SoundbiteClear,
    },

    setup(): { dispatch: Function } {
        return {
            dispatch: injectStore().dispatch,
        }
    },

    created() {
        this.dispatch(episode.init())
    }
})
</script>

<style lang="postcss">
</style>
