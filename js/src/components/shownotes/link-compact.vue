<template>
  <sn-card :htmlClass="isHidden ? 'bg-gray-100 opacity-60' : ''">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3 text-gray-500 truncate">
        <icon-menu htmlClass="w-5 h-5 flex-shrink-0 cursor-move drag-handle" />
        <div class="truncate">
          <div class="flex items-center gap-1">
            <img
              v-if="entry && entry.icon"
              :src="icon"
              width="16"
              height="16"
            />
            <div v-else style="width: 16px; height: 16px"></div>

            <span class="text-sm truncate text-gray-800 font-bold">
              <a :href="entry.url" target="_blank">{{ entry.title }}</a>
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2.5 text-gray-500">
        <span
          v-if="!isHidden"
          title="hide (hidden items do not appear in public shownotes)"
          @click.prevent="$emit('toggleHidden')"
        >
          <icon-eye htmlClass="w-5 h-5 cursor-pointer" />
        </span>
        <span
          v-else
          title="show (hidden items do not appear in public shownotes)"
          @click.prevent="$emit('toggleHidden')"
        >
          <icon-eye-off htmlClass="w-5 h-5 cursor-pointer" />
        </span>
        <span title="edit" @click.prevent="$emit('enableEdit')">
          <icon-edit htmlClass="w-5 h-5 cursor-pointer" />
        </span>
      </div>
    </div>
  </sn-card>
</template>

<script>
import Menu from "../icons/Menu";
import Edit from "../icons/Edit";
import Eye from "../icons/Eye";
import EyeOff from "../icons/EyeOff";
import SNCard from "./sn-card.vue";

export default {
  props: ["isHidden", "entry", "icon"],
  data() {
    return {};
  },
  components: {
    "icon-menu": Menu,
    "icon-edit": Edit,
    "icon-eye": Eye,
    "icon-eye-off": EyeOff,
    "sn-card": SNCard,
  },
};
</script>
