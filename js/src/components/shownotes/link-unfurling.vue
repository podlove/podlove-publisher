<template>
  <sn-card>
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3 text-gray-500">
        <icon-menu htmlClass="w-5 h-5 drag-handle" />
        <div>
          <div class="flex items-center gap-1">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4 animate-spin"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                clip-rule="evenodd"
                class="origin-center"
                transform="scale(1,-1)"
              />
            </svg>
            <div
              class="w-[300px] h-4 rounded-md bg-gray-300 animate-pulse"
            ></div>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2.5 text-gray-500">
        <icon-eye htmlClass="w-5 h-5" />
        <icon-edit htmlClass="w-5 h-5" />
      </div>
    </div>
  </sn-card>
</template>

<script>
import SNCard from "./sn-card.vue";
import Menu from "../icons/Menu";
import Eye from "../icons/Eye";
import Edit from "../icons/Edit";

export default {
  components: {
    "icon-menu": Menu,
    "icon-edit": Edit,
    "icon-eye": Eye,
    "sn-card": SNCard,
  },
};
</script>
