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
