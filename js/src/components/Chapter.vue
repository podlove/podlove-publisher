<template>
<div class="chapter" :class="{'active': active}" @click.prevent="$emit('activate')">
    <div class="time">
        {{ start.pretty }}
    </div>
    <div class="title">
        {{ title }}
    </div>
    <div class="duration">
        ???
    </div>
</div>
</template>

<script type="text/javascript">
export default {
    props: ['start', 'title', 'active'],

    data() {
        return {

        }
    }
}
</script>
