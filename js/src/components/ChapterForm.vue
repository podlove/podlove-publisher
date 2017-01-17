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
            <label for="chapter_start">Start Time</label>
            
                <label class="form-check-inline">
                    <input class="form-check-input" type="radio" name="timeformat" value="ms" autocomplete="off" v-model="timeformat"> milliseconds
                </label>
                <label class="form-check-inline">
                    <input class="form-check-input" type="radio" name="timeformat" value="hr" autocomplete="off" v-model="timeformat"> human readable
                </label>

            <input 
                type="text" 
                class="form-control" 
                id="chapter_start" 
                :value="formatTime(chapter.start.totalMs)" 
                v-on:input="chapter.start.totalMs = unformatTime($event.target.value)" 
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
import Timestamp from "../timestamp"

export default {

    data () {
        return {
            timeformat: 'ms'
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
                const timestamp = Timestamp.fromString(t);

                if (this.timeformat == 'hr') {
                    return timestamp.pretty;
                } else {
                    return timestamp.totalMs;
                }
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
        }
    }
}
</script>
