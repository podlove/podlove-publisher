<template>
<div class="chapter" :class="{'active': active}" @click.prevent="$emit('activate')">
    <div class="time">
        {{ start.pretty }}
    </div>
    <div class="title">
        {{ title }}
    </div>
    <div class="duration">
        {{ prettyDuration }}
    </div>
</div>
</template>

<script type="text/javascript">
import Timestamp from "../timestamp"

export default {
    props: ['start', 'title', 'active', 'duration'],

    data() {
        return {

        }
    },

    computed: {
        prettyDuration: function() {
            if (this.duration >= 0) {
                return (new Timestamp(this.duration)).prettyShort;
            } else {
                return "unknown";
            }
        }
    }
}
</script>

<style type="text/css">
.chapter {
    padding: 2px;
    display: flex;
    cursor: pointer;
}

.chapter:hover,
.chapter.active {
    background: #EEE;
}

.title {
    flex-grow: 4;
    text-align: left;
}

.duration {
    flex-grow: 1;
    text-align: right;
    cursor: text;
    font-family: monospace;
}

.time {
    /*flex-grow: 2;*/
    width: 105px;
    text-align: left;
    cursor: text;
    font-family: monospace;
}
</style>
