<template>
<div class="chapter" :class="{'active': active}" @click="$emit('activate')">
    <div class="time">
        {{ start.pretty }}
    </div>
    <div class="title">
        <span v-if="href">
            <a :href="href" :title="href" target="_blank">{{ title }}</a>
        </span>
        <span v-else>
            {{ title }}
        </span>
    </div>
    <div class="duration">
        {{ prettyDuration }}
    </div>
</div>
</template>

<script type="text/javascript">
import Timestamp from '../lib/timestamp'
import DurationErrors from '../lib/duration_errors'

export default {
    props: ['start', 'title', 'active', 'duration', 'href'],

    data() {
        return {

        }
    },

    computed: {
        prettyDuration: function() {
            if (this.duration >= 0) {
                return (new Timestamp(this.duration)).prettyShort;
            } else {
                switch (this.duration) {
                    case DurationErrors.LONGER_THAN_TOTAL:
                        return 'Exceeds episode length';
                    break;
                    case DurationErrors.TOTAL_UNKNOWN:
                        return 'Unknown episode length';
                    break;
                    case DurationErrors.TOTAL_INVALID:
                        return 'Invalid episode length';
                    break;
                }
                return 'unknown';
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

.chapter:nth-child(odd) {
    background: #F6F6F6;
}

.chapter:hover,
.chapter.active {
    background: #DDD;
}

.title {
    flex-grow: 4;
    text-align: left;
}

.duration {
    flex-grow: 1;
    text-align: right;
    font-family: monospace;
}

.time {
    width: 105px;
    text-align: left;
    font-family: monospace;
}
</style>
