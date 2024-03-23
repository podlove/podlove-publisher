<template>
    <div>
        <div>
            <Steps :steps="steps"></Steps>
        </div>
        <div class="mt-4 bg-white">
            <div v-if="aktStep === 0">
                <OnboardingPodcast></OnboardingPodcast>
            </div>
            <div v-else-if="aktStep === 1">
                <h1 class="text-3xl">Übersicht über die Eingabe-Daten</h1>
            </div>
            <div v-else>
                <h1 class="text-3xl">Was sind die nächsten Schritte</h1>
            </div>
        </div>
        <div v-if="aktStep < 2" class="flex justify-end">
            <div class="py-4">
                <PodloveButton variant="primary" @click="setNextStep()">{{__('Next')}}</PodloveButton>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Steps, { Step } from '@components/steps/Steps.vue'
import PodloveButton from '@components/button/Button.vue'
import OnboardingPodcast from './OnboardingPodcast.vue'

const steps : Step[] = [
    {id: 1, name: 'Podcast informations', status: 'current'},
    {id: 2, name: 'Preview', status: 'upcoming'},
    {id: 3, name: 'Next steps', status: 'upcoming'}
]

export default defineComponent({
    components: {
        Steps,
        PodloveButton,
        OnboardingPodcast,
    },
    data() {
        return {
            steps,
            aktStep: 0
        }
    },
    methods: {
        setNextStep() {
            if (this.aktStep + 1 < this.steps.length) {
                this.steps[this.aktStep].status = 'complete'
                this.steps[this.aktStep+1].status = 'current'
                this.aktStep += 1
            }
        }
    }
})
</script>

<style lang="postcss">
</style>