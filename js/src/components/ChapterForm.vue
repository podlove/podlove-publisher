<template>
    <div class="col-md-4 chapter-edit" v-if="chapter">
        <div class="form-group">
            <label for="chapter_title">Title</label>
            <input 
                type="text" 
                class="form-control" 
                id="chapter_title" 
                v-model="chapter.title" 
                @keyup.esc="unselectChapter"
                @keyup.up="selectPrevChapter"
                @keyup.down="selectNextChapter"
                >
        </div>
        <div class="form-group">
            <label for="chapter_url">URL <small>(optional)</small></label>

            <input 
                type="text" 
                class="form-control" 
                id="chapter_url" 
                v-model="chapter.href" 
                @keyup.esc="unselectChapter"
                @keyup.up="selectPrevChapter"
                @keyup.down="selectNextChapter"
                >
        </div>
        <div class="form-group">
            <label for="chapter_start">Start Time</label>

            <input 
                type="text" 
                class="form-control" 
                id="chapter_start" 
                :value="formatTime(chapter.start.totalMs)" 
                v-on:input="handleTimeInput" 
                @keyup.esc="unselectChapter"
                @keyup.up="selectPrevChapter"
                @keyup.down="selectNextChapter"
                >
        </div>

        <div class="form-group">
            <button class="button button-secondary delete-chapter" @click.prevent="deleteActiveChapter">Delete Chapter</button>
        </div>
    </div>
</template>

<script type="text/javascript">
import Timestamp from "../lib/timestamp"

export default {

    data () {
        return {
        }
    },

    props: {
        chapter: { default: null }
    },

    methods: {
        deleteActiveChapter() {
            this.$emit('deleteChapter', this.chapter);
        },
        formatTime(t) {
            try {
                return Timestamp.fromString(t).pretty;
            } catch (e) {
                return t;
            }
        },
        unformatTime(t) {
            return Timestamp.fromString(t).totalMs;
        },
        unselectChapter() {
            this.$emit('unselectChapter');
        },
        selectPrevChapter() {
            this.$emit('selectPrevChapter');
        },
        selectNextChapter() {
            this.$emit('selectNextChapter');
        },
        handleTimeInput: _.debounce(function (e) {
            this.chapter.start.totalMs = this.unformatTime(e.target.value);
        }, 1500)
    }
}
</script>
